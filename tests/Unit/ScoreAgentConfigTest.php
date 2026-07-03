<?php

use App\Screening\Agents\ScoreAgent;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Tests\TestCase;

// The config-wiring assertion needs the Laravel app booted (config repository).
uses(TestCase::class);

/**
 * Locks the two knobs that make scoring reproducible and configurable:
 * temperature 0 (same applicant → same Score) and the OLLAMA_MODEL wiring
 * (previously a dead env var — the provider config lacked the models.text.default
 * key, so the SDK always fell back to its hardcoded default).
 */
it('scores at temperature 0 for reproducible Scores', function () {
    $options = TextGenerationOptions::forAgent(new ScoreAgent);

    expect($options->temperature)->toBe(0.0);
});

it('wires the local scoring model through the ollama provider config', function () {
    // The key the OllamaProvider reads for its default text model. If this is
    // missing, OLLAMA_MODEL silently does nothing.
    $model = config('ai.providers.ollama.models.text.default');

    expect($model)->toBeString()->not->toBeEmpty();

    config()->set('ai.providers.ollama.models.text.default', 'sentinel-model:latest');

    expect(config('ai.providers.ollama.models.text.default'))->toBe('sentinel-model:latest');
});
