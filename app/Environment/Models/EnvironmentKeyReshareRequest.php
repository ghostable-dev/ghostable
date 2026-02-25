<?php

declare(strict_types=1);

namespace App\Environment\Models;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentKeyReshareRequest extends Model
{
    use HasUuids;

    protected $table = 'environment_key_reshare_requests';

    /** @var string[] */
    protected $fillable = [
        'organization_id',
        'project_id',
        'environment_id',
        'required_key_version',
        'target_user_id',
        'target_device_id',
        'status',
        'trigger_source',
        'resolved_at',
        'resolved_by_user_id',
        'cancel_reason',
        'last_notified_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'required_key_version' => 'integer',
        'status' => EnvironmentKeyReshareRequestStatus::class,
        'resolved_at' => 'datetime',
        'last_notified_at' => 'datetime',
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

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'target_device_id');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
