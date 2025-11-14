<?php

declare(strict_types=1);

namespace App\Crypto\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Envelope extends Model
{
    use HasUuids;

    protected $table = 'envelopes';

    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'owner_type',
        'owner_id',
        'alg',
        'nonce_b64',
        'ciphertext_b64',
        'aad_b64',
        'recipients',
        'version',
        'revoked_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'recipients' => 'array',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function hasBeenConsumed(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isActive(): bool
    {
        return ! $this->hasBeenConsumed();
    }

    public function isInactive(): bool
    {
        return $this->hasBeenConsumed();
    }

    public function markConsumed(): void
    {
        if ($this->hasBeenConsumed()) {
            return;
        }

        $this->forceFill(['revoked_at' => now()])->save();
    }
}
