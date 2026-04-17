<?php

declare(strict_types=1);

namespace App\Environment\Models;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentVariablePromotionRequestStatus;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentVariablePromotionRequest extends Model
{
    use HasUuids;

    protected $table = 'environment_variable_promotion_requests';

    /** @var string[] */
    protected $fillable = [
        'organization_id',
        'project_id',
        'source_environment_id',
        'target_environment_id',
        'request_device_id',
        'requested_by_user_id',
        'resolved_by_user_id',
        'status',
        'include_values',
        'target_key_version',
        'entries',
        'idempotency_key',
        'entries_hash',
        'rejected_reason',
        'cancel_reason',
        'resolved_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => EnvironmentVariablePromotionRequestStatus::class,
        'include_values' => 'boolean',
        'target_key_version' => 'integer',
        'entries' => 'array',
        'resolved_at' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function sourceEnvironment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'source_environment_id');
    }

    public function targetEnvironment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'target_environment_id');
    }

    public function requestDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'request_device_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
