<?php

use Laravel\Ai\Ai;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\StructuredAnonymousAgent;

use function Laravel\Ai\agent;

/*
|--------------------------------------------------------------------------
| Milestone 0 spike — laravel/ai v0.8.1 structured-output API
|--------------------------------------------------------------------------
|
| Goal: prove the SDK's schema/structured-output call returns the Score
| response contract, faked, so ApplicationScoringService can mirror it
| without ever hitting a real model. The exact call recorded below is what
| the scoring service will use.
|
| Recorded API (laravel/ai v0.8.1):
|   - Build an ad-hoc structured agent with the namespaced helper:
|         agent(instructions: '...', schema: fn (JsonSchema $s) => [...])
|     The `schema` closure receives an Illuminate\JsonSchema\JsonSchema
|     factory and returns array<string, Type> (the object's properties).
|   - Invoke it with ->prompt($text, provider: 'ollama'|'anthropic').
|   - A structured response is ArrayAccess + stringifies to JSON, and the
|     parsed payload is on ->structured. Provider is just an argument, so
|     the same code path serves Ollama (local) and Anthropic (prod).
|   - Tests fake the structured agent by class:
|         Ai::fakeAgent(StructuredAnonymousAgent::class, [$payload])
|
| A production agent will likely be a dedicated named Agent class
| implementing HasStructuredOutput so it can be faked by its own class
| name; the structured call shape is identical either way.
|
*/

/**
 * The locked Score response contract (see ralph.md / ADR 0004).
 */
function spikeScorePayload(): array
{
    return [
        'fit_score' => 82,
        'score_rationale' => 'Stable income comfortably covers the rent.',
        'summary' => 'The applicant reports steady employment and references. Income is well above the rent-to-income threshold. Application is complete and internally consistent.',
        'red_flags' => ['Move-in date is earlier than the unit is available.'],
        'strengths' => ['Rent-to-income ratio under 30%', 'Two contactable references provided'],
    ];
}

/**
 * Build the structured Score agent exactly as the scoring service will.
 */
function spikeScoreAgent(): StructuredAnonymousAgent
{
    return agent(
        instructions: 'You score rental applications using permissible factors only.',
        schema: fn ($schema) => [
            'fit_score' => $schema->integer()->min(0)->max(100)->description('Overall fit 0-100.'),
            'score_rationale' => $schema->string()->description('One-sentence rationale.'),
            'summary' => $schema->string()->description('2-3 neutral sentences.'),
            'red_flags' => $schema->array()->items($schema->string())->description('Permissible concerns.'),
            'strengths' => $schema->array()->items($schema->string())->description('Positive factors.'),
        ],
    );
}

test('a faked structured prompt returns the Score contract shape', function () {
    Ai::fakeAgent(StructuredAnonymousAgent::class, [spikeScorePayload()]);

    $response = spikeScoreAgent()->prompt('Score this application.', provider: 'ollama');

    expect($response)->toBeInstanceOf(StructuredAgentResponse::class);

    // Parsed structured payload — what the validator will consume.
    $parsed = $response->structured;

    expect($parsed)
        ->toHaveKeys(['fit_score', 'score_rationale', 'summary', 'red_flags', 'strengths'])
        ->and($parsed['fit_score'])->toBe(82)
        ->and($parsed['red_flags'])->toBeArray()
        ->and($parsed['strengths'])->toBeArray();

    // The same payload is reachable via ArrayAccess and as JSON.
    expect($response['fit_score'])->toBe(82);
    expect(json_decode((string) $response, true))->toBe(spikeScorePayload());
});

test('the structured call is provider-agnostic (ollama local / anthropic prod)', function () {
    Ai::fakeAgent(StructuredAnonymousAgent::class, [
        spikeScorePayload(),
        spikeScorePayload(),
    ]);

    foreach (['ollama', 'anthropic'] as $provider) {
        $response = spikeScoreAgent()->prompt('Score this application.', provider: $provider);

        expect($response['fit_score'])->toBe(82);
        expect($response->meta->provider)->toBe($provider);
    }
});
