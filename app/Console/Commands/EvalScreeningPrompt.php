<?php

namespace App\Console\Commands;

use App\Enums\AgentStatus;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Score;
use App\Models\Unit;
use App\Screening\ApplicationScoringService;
use App\Screening\ApplicationService;
use App\Screening\PromptEvaluation;
use App\Screening\ScoreResponseValidator;
use App\Screening\ScoreValidationResult;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('screening:eval-prompt
    {--samples=3 : How many times to score each profile (the model is nondeterministic)}
    {--profiles=strong,borderline,redflag : Comma-separated profiles to evaluate}
    {--round= : Report round number; auto-incremented from the last report when omitted}')]
#[Description('Score the fixture profiles against the live model and grade the result versus expectations.php.')]
class EvalScreeningPrompt extends Command
{
    /**
     * Where the fixture documents and the machine-readable ground truth live.
     */
    private const FIXTURES = 'tests/Fixtures/screening-samples';

    /**
     * Where round reports are written (real filesystem, not the faked disk).
     */
    private const REPORT_DIR = 'app/prompt-eval';

    /**
     * Run each fixture profile through the real submission + scoring path several
     * times, grade the medians against `expectations.php`, and write a round report.
     *
     * The whole run happens inside a rolled-back transaction on a faked filesystem
     * with mail and notifications faked, so it leaves the dev database and disk
     * exactly as it found them — only the model calls and the report file are real.
     * It hits the live model, so it must never run inside `artisan test`.
     */
    public function handle(): int
    {
        $dir = base_path(self::FIXTURES);

        if (! is_dir($dir)) {
            $this->error('Sample documents not found at '.self::FIXTURES.'. Generate them first.');

            return self::FAILURE;
        }

        $expectations = require $dir.'/expectations.php';
        $templates = SeedScreeningSamples::profiles();

        $profiles = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('profiles')))));

        foreach ($profiles as $profile) {
            if (! isset($expectations[$profile], $templates[$profile])) {
                $this->error("Unknown profile [{$profile}]. Known: ".implode(', ', array_keys($expectations)).'.');

                return self::FAILURE;
            }
        }

        $samples = max(1, (int) $this->option('samples'));

        // Isolate every side effect: documents to a temp disk, no real mail/notifications.
        Storage::fake('local');
        Mail::fake();
        Notification::fake();

        // Observe the FIRST validate() outcome per run so we can measure the
        // validator first-pass rate through the real service without touching it.
        $validator = new class extends ScoreResponseValidator
        {
            /** @var list<bool> */
            public array $outcomes = [];

            public function validate(mixed $payload): ScoreValidationResult
            {
                $result = parent::validate($payload);
                $this->outcomes[] = $result->valid;

                return $result;
            }
        };
        app()->instance(ScoreResponseValidator::class, $validator);

        $applications = app(ApplicationService::class);
        $scoring = app(ApplicationScoringService::class);

        $results = [];
        $index = 0;

        DB::beginTransaction();

        try {
            foreach ($profiles as $profile) {
                $expectation = $expectations[$profile];
                $unit = Unit::factory()->create([
                    'rent_amount' => $expectation['rent'],
                    'bedrooms' => 2,
                ]);

                $this->line("<info>{$profile}</info> — scoring {$samples}× at \${$expectation['rent']}/mo rent");

                $profileSamples = [];

                for ($i = 0; $i < $samples; $i++, $index++) {
                    $validator->outcomes = [];
                    $profileSamples[] = $this->scoreOnce($applications, $scoring, $unit, $dir, $profile, $templates[$profile], $index, $validator);
                }

                $results[$profile] = PromptEvaluation::evaluate($profile, $expectation, $profileSamples);
            }
        } finally {
            DB::rollBack();
        }

        $round = $this->resolveRound();
        $this->render($results);
        $this->write($round, $samples, $profiles, $results);

        return self::SUCCESS;
    }

    /**
     * Build one application through the real path, score it once, and reduce the
     * resulting Score to the sample shape {@see PromptEvaluation} grades.
     *
     * @param  array<string, mixed>  $template
     * @param  object{outcomes: list<bool>}  $validator
     * @return array{fit_score: int|null, rubric: array<string, string>, text: string, first_pass: bool, completed: bool}
     */
    private function scoreOnce(
        ApplicationService $applications,
        ApplicationScoringService $scoring,
        Unit $unit,
        string $dir,
        string $profile,
        array $template,
        int $index,
        object $validator,
    ): array {
        $answers = SeedScreeningSamples::buildAnswers($dir, $profile, $template, $index);

        $link = ApplicationLink::factory()->create([
            'unit_id' => $unit->id,
            'label' => "Eval · {$profile}",
            'is_accepting' => true,
        ]);

        $application = $applications->createApplication($link, $answers, null);

        try {
            $agent = $scoring->run($application);
            $completed = $agent->status === AgentStatus::Completed;
        } catch (Throwable $exception) {
            $this->warn("  sample {$index}: scoring threw — {$exception->getMessage()}");
            $completed = false;
        }

        $firstPass = ($validator->outcomes[0] ?? false) === true;
        $score = $application->score()->first();

        return [
            'fit_score' => $score?->fit_score,
            'rubric' => $this->rubricMap($score?->rubric),
            'text' => $this->corpus($application, $score),
            'first_pass' => $firstPass,
            'completed' => $completed && $score !== null,
        ];
    }

    /**
     * Reduce a Score rubric (list of rows) to a criterion ⇒ assessment map.
     *
     * @param  array<int, array{criterion: string, assessment: string, note: string}>|null  $rubric
     * @return array<string, string>
     */
    private function rubricMap(?array $rubric): array
    {
        $map = [];

        foreach ($rubric ?? [] as $row) {
            $map[$row['criterion']] = $row['assessment'];
        }

        return $map;
    }

    /**
     * The text the must-flag and forbidden regexes scan: summary + rationale +
     * flags + rubric notes. When a sample failed to validate there is no Score, so
     * we fall back to the raw model payload so protected-class leakage is still
     * caught even on a rejected response.
     */
    private function corpus(Application $application, ?Score $score): string
    {
        if ($score !== null) {
            $parts = array_merge(
                [(string) $score->summary, (string) $score->score_rationale],
                $score->red_flags ?? [],
                array_map(fn (array $row): string => $row['note'], $score->rubric ?? []),
            );

            return implode("\n", $parts);
        }

        $raw = $application->scoreAgent()->first()?->raw_response;

        return is_array($raw) ? (string) json_encode($raw) : (string) $raw;
    }

    /**
     * Render the scorecard as a console table, one row per profile.
     *
     * @param  array<string, array<string, mixed>>  $results
     */
    private function render(array $results): void
    {
        $rows = [];

        foreach ($results as $result) {
            $rows[] = [
                $result['profile'],
                $result['median_fit'] === null ? 'n/a' : (string) $result['median_fit'],
                "{$result['fit_min']}–{$result['fit_max']}",
                $this->tally($result['rubric']),
                $this->tally($result['must_flags']),
                $result['forbidden'] === [] ? '0' : (string) count($result['forbidden']),
                round($result['validator_first_pass_rate'] * 100).'%',
                $result['pass'] ? 'PASS' : 'FAIL',
            ];
        }

        $this->newLine();
        $this->table(
            ['Profile', 'Median', 'Band', 'Rubric', 'Flags', 'Forbidden', '1st-pass', 'Verdict'],
            $rows,
        );

        foreach ($results as $result) {
            foreach ($result['reasons'] as $reason) {
                $this->line("  <fg=red>✗</> {$result['profile']}: {$reason}");
            }
        }
    }

    /**
     * "passed/total" over a set of graded checks.
     *
     * @param  array<string, array{pass: bool}>  $checks
     */
    private function tally(array $checks): string
    {
        $passed = count(array_filter($checks, fn (array $check): bool => $check['pass']));

        return $passed.'/'.count($checks);
    }

    /**
     * Resolve the round number: the `--round` option, or one past the highest
     * `round-NN` report already on disk (00 when there are none).
     */
    private function resolveRound(): int
    {
        $option = $this->option('round');

        if ($option !== null && $option !== '') {
            return (int) $option;
        }

        $path = storage_path(self::REPORT_DIR);
        $highest = -1;

        foreach (File::exists($path) ? File::glob($path.'/round-*.json') : [] as $file) {
            if (preg_match('/round-(\d+)\.json$/', $file, $matches) === 1) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        return $highest + 1;
    }

    /**
     * Write the round report as JSON (full sample data) and Markdown (readable
     * scorecard) to `storage/app/prompt-eval/round-NN.{json,md}`.
     *
     * @param  list<string>  $profiles
     * @param  array<string, array<string, mixed>>  $results
     */
    private function write(int $round, int $samples, array $profiles, array $results): void
    {
        $path = storage_path(self::REPORT_DIR);
        File::ensureDirectoryExists($path);

        $nn = str_pad((string) $round, 2, '0', STR_PAD_LEFT);
        $passed = count(array_filter($results, fn (array $r): bool => $r['pass']));

        $json = [
            'round' => $round,
            'samples' => $samples,
            'profiles' => $profiles,
            'model' => (string) config('ai.default'),
            'passed' => $passed,
            'total' => count($results),
            'results' => $results,
        ];

        File::put("{$path}/round-{$nn}.json", (string) json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        File::put("{$path}/round-{$nn}.md", $this->markdown($nn, $json));

        $this->newLine();
        $this->info("Round {$nn}: {$passed}/".count($results).' profiles passed → storage/'.self::REPORT_DIR."/round-{$nn}.md");
    }

    /**
     * Render the Markdown report body.
     *
     * @param  array{round: int, samples: int, model: string, passed: int, total: int, results: array<string, array<string, mixed>>}  $data
     */
    private function markdown(string $nn, array $data): string
    {
        $lines = [
            "# Prompt eval — round {$nn}",
            '',
            "- **Model:** `{$data['model']}`",
            "- **Samples per profile:** {$data['samples']}",
            "- **Passed:** {$data['passed']}/{$data['total']}",
            '',
            '| Profile | Median fit | Band | Rubric | Must-flags | Forbidden | 1st-pass | Verdict |',
            '| --- | --- | --- | --- | --- | --- | --- | --- |',
        ];

        foreach ($data['results'] as $r) {
            $median = $r['median_fit'] === null ? 'n/a' : (string) $r['median_fit'];
            $lines[] = "| {$r['profile']} | {$median} | {$r['fit_min']}–{$r['fit_max']} | "
                .$this->tally($r['rubric']).' | '.$this->tally($r['must_flags']).' | '
                .count($r['forbidden']).' | '.round($r['validator_first_pass_rate'] * 100).'% | '
                .($r['pass'] ? 'PASS' : 'FAIL').' |';
        }

        foreach ($data['results'] as $r) {
            $lines[] = '';
            $lines[] = "## {$r['profile']} — ".($r['pass'] ? 'PASS' : 'FAIL');

            if ($r['reasons'] !== []) {
                $lines[] = '';
                foreach ($r['reasons'] as $reason) {
                    $lines[] = "- ✗ {$reason}";
                }
            }

            $lines[] = '';
            $lines[] = '| Criterion | Allowed | Distribution | Hold |';
            $lines[] = '| --- | --- | --- | --- |';
            foreach ($r['rubric'] as $criterion => $grade) {
                $distParts = [];
                foreach ($grade['distribution'] as $assessment => $count) {
                    $distParts[] = "{$assessment}×{$count}";
                }
                $dist = implode(', ', $distParts);
                $lines[] = "| {$criterion} | ".implode('/', $grade['allowed'])." | {$dist} | "
                    .($grade['pass'] ? '✓' : '✗')." {$grade['hits']}/".array_sum($grade['distribution']).' |';
            }

            if ($r['must_flags'] !== []) {
                $lines[] = '';
                $lines[] = '| Must-flag | Hit rate | Hold |';
                $lines[] = '| --- | --- | --- |';
                foreach ($r['must_flags'] as $label => $grade) {
                    $lines[] = "| {$label} | ".round($grade['rate'] * 100).'% | '.($grade['pass'] ? '✓' : '✗').' |';
                }
            }
        }

        return implode("\n", $lines)."\n";
    }
}
