<?php

namespace App\Screening;

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Score;
use App\Screening\Agents\ScoreAgent;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

/**
 * The `score` {@see AgentHandler}: runs the AI engine over an {@see Application}
 * and produces its {@see Score}.
 *
 * Flow: locate/create the application's single score {@see Agent} (1:1, mutated in
 * place on re-runs) and mark it processing → build the prompt from the answers and
 * extracted document text → call the SDK in structured-output mode → validate the
 * payload, with a single repair retry on failure → on success persist the Score and
 * complete the Agent; on hard failure mark the Agent failed (storing the raw payload)
 * and write no Score.
 */
class ApplicationScoringService implements AgentHandler
{
    public function __construct(
        private readonly DocumentTextExtractor $documentExtractor,
        private readonly ScoreResponseValidator $validator,
    ) {}

    /**
     * Run the score handler against the given subject.
     *
     * @throws InvalidArgumentException When the subject is not an Application.
     */
    public function run(Model $analyzable): Agent
    {
        if (! $analyzable instanceof Application) {
            throw new InvalidArgumentException(
                'The score handler can only analyze applications, given ['.$analyzable::class.'].'
            );
        }

        return $this->score($analyzable);
    }

    /**
     * Score the given application, returning its (now mutated) score Agent record.
     */
    public function score(Application $application): Agent
    {
        $provider = (string) config('ai.default');
        $agent = $this->startAgent($application, $provider);

        try {
            $documentText = $this->documentExtractor->extractFromMany($application->documents);
            $prompt = ScorePrompt::forApplication($application, $documentText);

            $response = (new ScoreAgent)->prompt($prompt, provider: $provider);
            $result = $this->validator->validate($response->structured);

            if (! $result->valid) {
                $response = (new ScoreAgent)->prompt(
                    $this->repairPrompt($prompt, $result->errors),
                    provider: $provider,
                );
                $result = $this->validator->validate($response->structured);
            }

            if (! $result->valid) {
                return $this->failAgent($agent, $response, $result->errors);
            }

            return $this->completeAgent($application, $agent, $response, $result->value);
        } catch (Throwable $exception) {
            return $this->failAgent($agent, null, [$exception->getMessage()]);
        }
    }

    /**
     * Locate or create the application's score Agent and mark it processing.
     */
    private function startAgent(Application $application, string $provider): Agent
    {
        $agent = $application->scoreAgent()->firstOrNew();

        $agent->type = AgentType::Score;
        $agent->provider = $provider;
        $agent->status = AgentStatus::Processing;
        $agent->started_at = now();
        $agent->completed_at = null;
        $agent->error = null;
        $agent->save();

        return $agent;
    }

    /**
     * Persist the validated Score (1:1) and mark the Agent completed.
     *
     * @param  array{fit_score: int, score_rationale: string, summary: string, red_flags: list<string>, strengths: list<string>}  $value
     */
    private function completeAgent(
        Application $application,
        Agent $agent,
        StructuredAgentResponse $response,
        array $value,
    ): Agent {
        $agent->status = AgentStatus::Completed;
        $agent->model = $response->meta->model;
        $agent->usage = $response->usage->toArray();
        $agent->raw_response = $response->structured;
        $agent->completed_at = now();
        $agent->error = null;
        $agent->save();

        $score = $application->score()->firstOrNew();
        $score->agent()->associate($agent);
        $score->fill($value);
        $score->save();

        return $agent;
    }

    /**
     * Mark the Agent failed, storing the last raw payload, and write no Score.
     *
     * @param  list<string>  $errors
     */
    private function failAgent(Agent $agent, ?StructuredAgentResponse $response, array $errors): Agent
    {
        $agent->status = AgentStatus::Failed;
        $agent->model = $response?->meta->model ?? $agent->model;
        $agent->raw_response = $response?->structured;
        $agent->error = implode(' ', $errors);
        $agent->completed_at = now();
        $agent->save();

        return $agent;
    }

    /**
     * Re-ask the model with the contract violations appended, for one repair retry.
     *
     * @param  list<string>  $errors
     */
    private function repairPrompt(string $prompt, array $errors): string
    {
        $issues = implode("\n", array_map(fn (string $error): string => "- {$error}", $errors));

        return $prompt."\n\n=== YOUR PREVIOUS RESPONSE WAS INVALID ===\n"
            ."It did not satisfy the response contract:\n{$issues}\n"
            .'Return a corrected JSON object that satisfies every requirement above.';
    }
}
