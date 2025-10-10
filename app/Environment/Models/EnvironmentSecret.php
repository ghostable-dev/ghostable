<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    protected function displayLineBytes(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bytes = $this->line_bytes;

                if ($bytes === null) {
                    return null;
                }

                // Round to nearest meaningful size
                return match (true) {
                    $bytes < 1024 => "~{$bytes} bytes",
                    $bytes < 10 * 1024 => '~1 KB',
                    $bytes < 100 * 1024 => sprintf('~%d KB', round($bytes / 1024, -1)), // round to nearest 10 KB
                    $bytes < 1024 * 1024 => sprintf('~%d KB', round($bytes / 1024)), // nearest KB
                    $bytes < 10 * 1024 * 1024 => sprintf('~%d MB', round($bytes / (1024 * 1024), 1)), // nearest tenth of MB
                    default => sprintf('~%d MB', round($bytes / (1024 * 1024))), // nearest MB
                };
            }
        );
    }

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

    public function latestVersion(): HasOne
    {
        return $this->hasOne(EnvironmentSecretVersion::class)
            ->orderByDesc('version');
    }

    /**
     * Convenience: does this row belong to the given environment?
     */
    public function belongsToEnvironment(Environment $environment): bool
    {
        return $this->environment_id === $environment->id;
    }
}
