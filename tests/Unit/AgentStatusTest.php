<?php

use App\Enums\AgentStatus;

test('agent statuses report their label', function () {
    expect(AgentStatus::Pending->label())->toBe('Pending');
    expect(AgentStatus::Processing->label())->toBe('Processing');
    expect(AgentStatus::Completed->label())->toBe('Completed');
    expect(AgentStatus::Failed->label())->toBe('Failed');
});

test('agent statuses map to their string values', function () {
    expect(AgentStatus::Pending->value)->toBe('pending');
    expect(AgentStatus::Processing->value)->toBe('processing');
    expect(AgentStatus::Completed->value)->toBe('completed');
    expect(AgentStatus::Failed->value)->toBe('failed');
});

test('there are four agent statuses', function () {
    expect(AgentStatus::cases())->toHaveCount(4);
});
