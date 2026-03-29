<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use Database\Factories\EnvironmentVariableVersionChangeNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentVariableVersionChangeNote extends Model
{
    /** @use HasFactory<EnvironmentVariableVersionChangeNoteFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'environment_secret_version_id',
        'ciphertext',
        'nonce',
        'alg',
        'aad',
        'claims',
        'client_sig',
        'created_by',
    ];

    protected $casts = [
        'aad' => 'array',
        'claims' => 'array',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(EnvironmentSecretVersion::class, 'environment_secret_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
