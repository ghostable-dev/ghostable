<?php

namespace App\Environment\Models;

use App\Auth\Models\PersonalAccessToken;
use App\Project\Models\Project;
use Database\Factories\DeploymentTokenFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentToken extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'deployment_tokens';

    protected $fillable = [
        'environment_id',
        'name',
        'personal_access_token_id',
        'project_id',
        'public_key',
        'revoked_at',
        'token_suffix',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    protected static function newFactory(): DeploymentTokenFactory
    {
        return DeploymentTokenFactory::new();
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function personalAccessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class);
    }

    public function isRevoked(): bool
    {
        return (bool) $this->revoked_at;
    }

    public function markRevoked(): void
    {
        if ($this->isRevoked()) {
            return;
        }

        $this->forceFill([
            'revoked_at' => now(),
            'personal_access_token_id' => null,
        ])->save();
    }
}
