<?php

namespace App\Models;

use App\Enums\AgentStatus;
use App\Enums\AgentType;
use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $analyzable_type
 * @property int $analyzable_id
 * @property AgentType $type
 * @property string|null $provider
 * @property string|null $model
 * @property AgentStatus $status
 * @property array<string, mixed>|null $raw_response
 * @property array<string, mixed>|null $usage
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $subject_label
 * @property-read string|null $result_url
 */
#[Fillable([
    'type',
    'provider',
    'model',
    'status',
    'raw_response',
    'usage',
    'error',
    'started_at',
    'completed_at',
])]
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AgentType::class,
            'status' => AgentStatus::class,
            'raw_response' => 'array',
            'usage' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The subject this agent analyzes (e.g. an Application).
     *
     * @return MorphTo<Model, $this>
     */
    public function analyzable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-readable label for the analyzed subject, delegated to the subject.
     *
     * @return Attribute<string|null, never>
     */
    protected function subjectLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->analyzable?->agentLabel());
    }

    /**
     * URL to view the agent's result, delegated to the subject.
     *
     * @return Attribute<string|null, never>
     */
    protected function resultUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->analyzable?->agentUrl());
    }
}
