<?php

declare(strict_types=1);

namespace App\Crypto\Models;

use App\Account\Models\User;
use App\Crypto\Enums\DeviceClientType;
use App\Crypto\Enums\DevicePlatform;
use App\Licensing\Models\LicenseActivation;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'devices';

    /** @var string[] */
    protected $fillable = [
        'active',
        'app_version',
        'client_type',
        'last_seen_at',
        'name',
        'platform',
        'public_key',
        'public_signing_key',
        'revoked_at',
        'user_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'active' => 'boolean',
        'client_type' => DeviceClientType::class,
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function newFactory(): DeviceFactory
    {
        return DeviceFactory::new();
    }

    /**
     * The user who owns or registered this device.
     * Used to scope devices under an account or team.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function licenseActivations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    protected function platform(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?DevicePlatform => $value === null || $value === ''
                ? null
                : DevicePlatform::fromStorageValue((string) $value),
            set: fn (mixed $value): ?string => $value === null || $value === ''
                ? null
                : match (true) {
                    $value instanceof DevicePlatform => $value->value,
                    default => DevicePlatform::fromStorageValue((string) $value)->value,
                },
        );
    }

    /**
     * Updates the device's "last seen" timestamp.
     *
     * Call this whenever the device successfully communicates
     * with the server (e.g., syncs crypto state, pulls envelopes).
     * It provides a quick way to track device activity and presence.
     */
    public function markSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Checks whether this device is currently revoked.
     *
     * Returns true if the device has been explicitly marked inactive
     * or has a revocation timestamp. Revoked devices are considered
     * permanently invalid for future cryptographic operations.
     */
    public function isRevoked(): bool
    {
        return ! $this->active || ! is_null($this->revoked_at);
    }
}
