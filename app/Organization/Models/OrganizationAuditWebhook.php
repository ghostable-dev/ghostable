<?php

declare(strict_types=1);

namespace App\Organization\Models;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationAuditWebhookStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $endpoint_url
 * @property string $signing_secret
 * @property OrganizationAuditWebhookStatus $status
 * @property int $consecutive_failures
 * @property Carbon|null $last_delivered_at
 * @property Carbon|null $disabled_at
 * @property Carbon|null $dead_lettered_at
 * @property string|null $last_error
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OrganizationAuditWebhook extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'name',
        'endpoint_url',
        'signing_secret',
        'status',
        'consecutive_failures',
        'last_delivered_at',
        'disabled_at',
        'dead_lettered_at',
        'last_error',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => OrganizationAuditWebhookStatus::class,
        'signing_secret' => 'encrypted',
        'consecutive_failures' => 'integer',
        'last_delivered_at' => 'datetime',
        'disabled_at' => 'datetime',
        'dead_lettered_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(OrganizationAuditWebhookDelivery::class, 'organization_audit_webhook_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
