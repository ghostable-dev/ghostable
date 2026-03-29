<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use Database\Factories\EnvironmentVariableNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentVariableNote extends Model
{
    /** @use HasFactory<EnvironmentVariableNoteFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'environment_secret_id',
        'ciphertext',
        'nonce',
        'alg',
        'aad',
        'claims',
        'client_sig',
        'created_by',
        'last_updated_by',
    ];

    protected $casts = [
        'aad' => 'array',
        'claims' => 'array',
    ];

    public function secret(): BelongsTo
    {
        return $this->belongsTo(EnvironmentSecret::class, 'environment_secret_id')
            ->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
