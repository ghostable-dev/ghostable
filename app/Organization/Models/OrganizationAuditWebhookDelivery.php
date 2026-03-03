<?php

declare(strict_types=1);

namespace App\Organization\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $organization_audit_webhook_id
 * @property string $organization_id
 * @property string|null $event_id
 * @property string|null $event_type
 * @property string $status
 * @property int|null $http_status
 * @property int|null $latency_ms
 * @property int $attempt_number
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class OrganizationAuditWebhookDelivery extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_audit_webhook_id',
        'organization_id',
        'event_id',
        'event_type',
        'status',
        'http_status',
        'latency_ms',
        'attempt_number',
        'error_message',
        'delivered_at',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'latency_ms' => 'integer',
        'attempt_number' => 'integer',
        'delivered_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(OrganizationAuditWebhook::class, 'organization_audit_webhook_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
