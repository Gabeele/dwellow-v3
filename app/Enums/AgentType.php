<?php

namespace App\Enums;

enum AgentType: string
{
    case Score = 'score';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Score => 'Score',
        };
    }
}
