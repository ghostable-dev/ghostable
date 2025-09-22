<?php

namespace App\Messaging\Models;

use App\Messaging\Builders\MessageBuilder;
use App\Messaging\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[UseEloquentBuilder(MessageBuilder::class)]
/**
 * @property string $id
 * @property string $recipient_type
 * @property string $recipient_id
 * @property string $recipient_email
 * @property string $campaign_key
 * @property MessageStatus $status
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $queued_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $recipient
 *
 * @method static MessageBuilder<static>|Message failed()
 * @method static MessageBuilder<static>|Message forCampaign(\App\Messaging\Contracts\Campaign $campaign)
 * @method static MessageBuilder<static>|Message newModelQuery()
 * @method static MessageBuilder<static>|Message newQuery()
 * @method static MessageBuilder<static>|Message query()
 * @method static MessageBuilder<static>|Message queued()
 * @method static MessageBuilder<static>|Message sent()
 * @method static MessageBuilder<static>|Message suppressed()
 * @method static MessageBuilder<static>|Message whereCampaignKey($value)
 * @method static MessageBuilder<static>|Message whereCreatedAt($value)
 * @method static MessageBuilder<static>|Message whereId($value)
 * @method static MessageBuilder<static>|Message whereMeta($value)
 * @method static MessageBuilder<static>|Message whereQueuedAt($value)
 * @method static MessageBuilder<static>|Message whereReason($value)
 * @method static MessageBuilder<static>|Message whereRecipientEmail($value)
 * @method static MessageBuilder<static>|Message whereRecipientId($value)
 * @method static MessageBuilder<static>|Message whereRecipientType($value)
 * @method static MessageBuilder<static>|Message whereSentAt($value)
 * @method static MessageBuilder<static>|Message whereStatus($value)
 * @method static MessageBuilder<static>|Message whereUpdatedAt($value)
 * @method static MessageBuilder<static>|Message withStatus(\App\Messaging\Enums\MessageStatus $status)
 *
 * @mixin \Eloquent
 */
class Message extends Model
{
    use HasUuids;

    protected $fillable = [
        'campaign_key',
        'status',
        'reason',
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
