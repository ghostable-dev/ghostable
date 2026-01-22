<?php

declare(strict_types=1);

namespace App\Integration\Models;

use App\Account\Models\User;
use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationToken extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'integration_client_id',
        'integration_id',
        'organization_id',
        'user_id',
        'access_token_hash',
        'access_token_expires_at',
        'refresh_token_hash',
        'refresh_token_expires_at',
        'scopes',
        'token_suffix',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'access_token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function integrationClient(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
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
