<?php

namespace App\Messaging\Models;

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
        'delivered_at',
        'opened_at',
        'clicked_at',
        'meta',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function recipient()
    {
        return $this->morphTo();
    }
}
