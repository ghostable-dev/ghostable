<?php

declare(strict_types=1);

namespace App\Integration\Models;

use App\Account\Models\User;
use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationAuthorizationCode extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'integration_client_id',
        'organization_id',
        'user_id',
        'code_hash',
        'scopes',
        'redirect_uri',
        'state',
        'code_challenge',
        'code_challenge_method',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function integrationClient(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
