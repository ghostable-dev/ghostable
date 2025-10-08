<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnvironmentSecret extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'environment_id',
        'name',
        'ciphertext',
        'nonce',
        'alg',
        'aad',
        'claims',
        'client_sig',
        'line_bytes',
        'is_vapor_secret',
        'is_commented',
        'is_override',
        'version',
        'last_updated_by',
        'last_updated_at',
    ];

    protected $casts = [
        'aad' => 'array',
        'claims' => 'array',
        'is_vapor_secret' => 'boolean',
        'is_commented' => 'boolean',
        'is_override' => 'boolean',
        'last_updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Current secret belongs to an Environment.
     */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    /**
     * User who last updated the current head row.
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * All historical versions (append-only snapshots).
     */
    public function versions(): HasMany
    {
        return $this->hasMany(EnvironmentSecretVersion::class, 'environment_secret_id');
    }

    /**
     * Convenience: does this row belong to the given environment?
     */
    public function belongsToEnvironment(Environment $environment): bool
    {
        return $this->environment_id === $environment->id;
    }
}
