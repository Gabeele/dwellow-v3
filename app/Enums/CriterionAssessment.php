<?php

namespace App\Enums;

use App\Screening\ScoringFramework;

/**
 * The verdict a single {@see ScoringFramework} criterion receives.
 *
 * A fixed four-point scale so every application is graded the same way: the
 * criterion either supports the tenancy (Strong), is acceptable (Adequate),
 * counts against it (Weak), or cannot be judged from what was submitted
 * (Unverified — missing or unreadable information, never a guess).
 */
enum CriterionAssessment: string
{
    case Strong = 'strong';
    case Adequate = 'adequate';
    case Weak = 'weak';
    case Unverified = 'unverified';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Strong => 'Strong',
            self::Adequate => 'Adequate',
            self::Weak => 'Weak',
            self::Unverified => 'Unverified',
        };
    }

    /**
     * The backing values, for schema enums and validation.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
