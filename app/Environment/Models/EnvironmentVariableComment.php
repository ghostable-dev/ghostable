<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use Database\Factories\EnvironmentVariableCommentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentVariableComment extends Model
{
    /** @use HasFactory<EnvironmentVariableCommentFactory> */
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
}
