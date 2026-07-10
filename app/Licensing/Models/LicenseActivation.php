<?php

declare(strict_types=1);

namespace App\Licensing\Models;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use Database\Factories\LicenseActivationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $license_id
 * @property string|null $user_id
 * @property string|null $device_id
 * @property string $activation_token_hash
 * @property string $machine_fingerprint_hash
 * @property string|null $machine_name
 * @property string $platform
 * @property string|null $app_version
 * @property Carbon|null $last_validated_at
 * @property Carbon|null $deactivated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read License $license
 * @property-read User|null $user
 * @property-read Device|null $device
 */
class LicenseActivation extends Model
{
    /** @use HasFactory<LicenseActivationFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'license_activations';

    /**
     * @var list<string>
     */
    protected $hidden = [
        'activation_token_hash',
        'machine_fingerprint_hash',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'license_id',
        'user_id',
        'device_id',
        'activation_token_hash',
        'machine_fingerprint_hash',
        'machine_name',
        'platform',
        'app_version',
        'last_validated_at',
        'deactivated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_validated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): LicenseActivationFactory
    {
        return LicenseActivationFactory::new();
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function status(): string
    {
        return $this->deactivated_at === null ? 'active' : 'deactivated';
    }
}
