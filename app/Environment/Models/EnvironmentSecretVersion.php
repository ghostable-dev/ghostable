<?php

namespace App\Environment\Models;

use App\Account\Models\User;
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
