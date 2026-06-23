<?php

use App\Enums\ApplicationStatus;

test('application statuses report their label', function () {
    expect(ApplicationStatus::New->label())->toBe('New');
    expect(ApplicationStatus::Reviewing->label())->toBe('Reviewing');
    expect(ApplicationStatus::Approved->label())->toBe('Approved');
    expect(ApplicationStatus::Rejected->label())->toBe('Rejected');
});

test('there are four application statuses', function () {
    expect(ApplicationStatus::cases())->toHaveCount(4);
});
