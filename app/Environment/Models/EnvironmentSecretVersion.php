<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentSecretVersion extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'environment_secret_id',
        'version',
        'name',
        'alg',
        'ciphertext',
        'nonce',
        'aad',
        'claims',
        'client_sig',
        'line_bytes',
        'is_vapor_secret',
        'is_commented',
        'is_override',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'aad' => 'array',
        'claims' => 'array',
        'is_vapor_secret' => 'boolean',
        'is_commented' => 'boolean',
        'is_override' => 'boolean',
        'created_at' => 'datetime',
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
     * Parent current/head secret.
     */
    public function secret(): BelongsTo
    {
        return $this->belongsTo(EnvironmentSecret::class, 'environment_secret_id');
    }

    /**
     * User who authored this version (if tracked).
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
