<?php

namespace App\Models;

use Database\Factories\SentEmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A record of an outgoing email, captured so an admin can review what was sent.
 *
 * @property int $id
 * @property string|null $mailer
 * @property string|null $subject
 * @property string|null $from
 * @property array<int, string> $to
 * @property array<int, string>|null $cc
 * @property array<int, string>|null $bcc
 * @property string|null $body
 * @property Carbon $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'mailer',
    'subject',
    'from',
    'to',
    'cc',
    'bcc',
    'body',
    'sent_at',
])]
class SentEmail extends Model
{
    /** @use HasFactory<SentEmailFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'to' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
