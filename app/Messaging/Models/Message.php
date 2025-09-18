<?php

namespace App\Messaging\Models;

use App\Messaging\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasUuids;

    protected $fillable = [
        'campaign_key',
        'status',
        'reason',
        'provider',
        'provider_message_id',
        'recipient_type',
        'recipient_id',
        'recipient_email',
        'queued_at',
        'sent_at',
        'meta',
    ];

    protected $casts = [
        'status' => MessageStatus::class,
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'meta' => 'array',
    ];

    public function recipient()
    {
        return $this->morphTo();
    }
}
