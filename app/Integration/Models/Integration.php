<?php

declare(strict_types=1);

namespace App\Integration\Models;

use App\Account\Models\User;
use App\Integration\Casts\IntegrationSettingsCast;
use App\Integration\Enums\IntegrationDirection;
use App\Integration\Enums\IntegrationStatus;
use App\Organization\Models\Organization;
use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integration extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'key',
        'settings',
        'secure_settings',
        'status',
        'direction',
        'integration_client_id',
        'approved_by_user_id',
        'approved_at',
        'connected_at',
    ];

    protected $casts = [
        'settings' => IntegrationSettingsCast::class,
        'secure_settings' => 'encrypted:array',
        'status' => IntegrationStatus::class,
        'direction' => IntegrationDirection::class,
        'approved_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    protected static function newFactory(): IntegrationFactory
    {
        return IntegrationFactory::new();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function integrationClient(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
