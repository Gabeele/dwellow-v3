<?php

use App\Models\Application;
use App\Models\Unit;
use App\Screening\ScorePrompt;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

/**
 * Build an in-memory (unpersisted) application with the given answers/snapshot.
 *
 * @param  array<string, mixed>  $answers
 * @param  list<array{key: string, label: string}>  $snapshot
 */
function scorePromptApplication(array $answers, array $snapshot): Application
{
    $application = new Application;
    $application->answers = $answers;
    $application->form_snapshot = $snapshot;

    return $application;
}

test('instructions carry the response contract schema', function () {
    $instructions = ScorePrompt::instructions();

    expect($instructions)
        ->toContain('fit_score')
        ->toContain('score_rationale')
        ->toContain('summary')
        ->toContain('rubric')
        ->toContain('red_flags')
        ->toContain('strengths')
        ->toContain('0-100');
});

test('instructions carry the fixed scoring framework: every criterion and the verdict scale', function () {
    $instructions = ScorePrompt::instructions();

    expect($instructions)
        ->toContain('SCORING FRAMEWORK')
        // every framework criterion is named
        ->toContain('affordability')
        ->toContain('employment')
        ->toContain('credit')
        ->toContain('references')
        ->toContain('rental_history')
        ->toContain('occupancy')
        ->toContain('identity')
        ->toContain('disclosures')
        // the fixed verdict scale
        ->toContain('strong')
        ->toContain('adequate')
        ->toContain('weak')
        ->toContain('unverified');
});

test('instructions ask the model to cross-reference documents against answers and the unit', function () {
    $instructions = ScorePrompt::instructions();

    expect($instructions)
        ->toContain('CROSS-REFERENCE')
        ->toContain('rent-to-income')
        ->toContain('Occupancy');
});

test('instructions carry the fair-housing guardrail with permissible and protected factors', function () {
    $instructions = ScorePrompt::instructions();

    expect($instructions)
        ->toContain('FAIR-HOUSING')
        ->toContain('rent-to-income')
        ->toContain('references')
        // protected classes / proxies must be named as off-limits
        ->toContain('NEVER')
        ->toContain('race')
        ->toContain('disability')
        ->toContain('source of income')
        // flags are permissible concerns only
        ->toContain('permissible concerns only');
});

test('instructions carry the unverified-data framing', function () {
    $instructions = ScorePrompt::instructions();

    expect($instructions)
        ->toContain('UNVERIFIED DATA')
        ->toContain('self-reported')
        ->toContain('screening AID');
});

test('the application body includes labelled answers and document text', function () {
    $application = scorePromptApplication(
        answers: [
            'gross_monthly_income' => '6000',
            'employer_name' => 'Acme Corp',
            'has_pets' => true,
            'previous_landlord' => ['name' => 'Jordan Lee', 'phone' => '555-0100'],
        ],
        snapshot: [
            ['key' => 'gross_monthly_income', 'label' => 'Gross monthly income'],
            ['key' => 'employer_name', 'label' => 'Employer name'],
            ['key' => 'has_pets', 'label' => 'Do you have any pets?'],
            ['key' => 'previous_landlord', 'label' => 'Previous or current landlord'],
        ],
    );

    $body = ScorePrompt::forApplication($application, "Pay stub: net pay 4200.\nEmployer: Acme Corp.");

    expect($body)
        ->toContain('Gross monthly income: 6000')
        ->toContain('Employer name: Acme Corp')
        ->toContain('Do you have any pets?: Yes')
        ->toContain('name: Jordan Lee')
        ->toContain('Pay stub: net pay 4200.')
        ->toContain('DOCUMENT TEXT');
});

test('the application body notes when no document text is supplied', function () {
    $application = scorePromptApplication(
        answers: ['employer_name' => 'Acme Corp'],
        snapshot: [['key' => 'employer_name', 'label' => 'Employer name']],
    );

    expect(ScorePrompt::forApplication($application))
        ->toContain('No document text was provided.');
});

test('the schema closure returns the contract properties', function () {
    $schema = ScorePrompt::schema();

    expect($schema)->toBeInstanceOf(Closure::class);

    $properties = $schema(new JsonSchemaTypeFactory);

    expect($properties)
        ->toBeArray()
        ->toHaveKeys(['fit_score', 'score_rationale', 'summary', 'rubric', 'red_flags', 'strengths']);
});

test('the application body includes the applied-for unit so the model can judge ratios', function () {
    $application = scorePromptApplication(
        answers: ['number_of_occupants' => '4'],
        snapshot: [['key' => 'number_of_occupants', 'label' => 'Number of occupants']],
    );
    // Set the relation in-memory so no database is touched.
    $application->setRelation('unit', new Unit([
        'label' => 'Unit 2B',
        'bedrooms' => 1,
        'bathrooms' => '1.0',
        'rent_amount' => '1850.00',
    ]));

    expect(ScorePrompt::forApplication($application))
        ->toContain('UNIT')
        ->toContain('Bedrooms: 1')
        ->toContain('1,850.00')
        ->toContain('Number of occupants: 4');
});
