<?php

namespace App\Screening;

use App\Enums\AgentType;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for a single agent type's analysis logic.
 *
 * Each {@see AgentType} has exactly one handler that runs the engine
 * for a subject and returns the {@see Agent} record describing the outcome
 * (its provider/model, status, usage, and — on success — the persisted result).
 * The `score` handler is {@see ApplicationScoringService}; future types
 * (e.g. maintenance-request triage) implement this same contract.
 *
 * There is deliberately no registry/manager — that is YAGNI until a second
 * agent type exists.
 */
interface AgentHandler
{
    /**
     * Run this handler against the given subject and return its Agent record.
     *
     * Implementations mutate the subject's single Agent of this type in place
     * (1:1), so re-runs/retries reuse the same row rather than creating new ones.
     */
    public function run(Model $analyzable): Agent;
}
