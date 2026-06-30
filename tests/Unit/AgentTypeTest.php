<?php

use App\Enums\AgentType;

test('agent types report their label', function () {
    expect(AgentType::Score->label())->toBe('Score');
});

test('agent types map to their string value', function () {
    expect(AgentType::Score->value)->toBe('score');
});

test('there is one agent type', function () {
    expect(AgentType::cases())->toHaveCount(1);
});
