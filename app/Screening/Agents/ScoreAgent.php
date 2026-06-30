<?php

namespace App\Screening\Agents;

use App\Models\Score;
use App\Screening\ScorePrompt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * The structured-output agent that produces a {@see Score}.
 *
 * It is a dedicated named class (rather than the anonymous `agent()` helper used
 * in the Milestone 0 spike) so tests fake it by its own class name —
 * `ScoreAgent::fake([...])` — without colliding with any other structured agent.
 * Both the system prompt and the response schema are owned by {@see ScorePrompt}
 * so the contract lives in one tunable place.
 */
class ScoreAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * The system prompt: role, fair-housing rules, unverified-data framing, contract.
     */
    public function instructions(): Stringable|string
    {
        return ScorePrompt::instructions();
    }

    /**
     * The structured-output schema, mirroring the Score response contract.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return (ScorePrompt::schema())($schema);
    }
}
