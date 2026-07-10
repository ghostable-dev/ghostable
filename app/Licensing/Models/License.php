<?php

declare(strict_types=1);

namespace App\Licensing\Models;

use App\Account\Models\User;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Enums\LicenseStatus;
use App\Organization\Models\Organization;
use Database\Factories\LicenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $organization_id
 * @property string|null $purchaser_user_id
 * @property LicensePlan $plan
 * @property LicenseStatus $status
 * @property string $purchaser_email
 * @property string $license_key_hash
 * @property string|null $encrypted_license_key
 * @property string|null $license_key_suffix
 * @property int $seat_count
 * @property int $activation_limit
 * @property Carbon|null $updates_until
 * @property Carbon|null $expires_at
 * @property string|null $provider
 * @property string|null $provider_customer_id
 * @property string|null $provider_checkout_id
 * @property string|null $provider_subscription_id
 * @property array<string, mixed>|null $provider_metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read User|null $purchaser
 * @property-read Collection<int, LicenseActivation> $activations
 * @property-read int|null $activations_count
 * @property-read Collection<int, LicenseActivation> $activeActivations
 * @property-read int|null $active_activations_count
 * @property-read Collection<int, LicenseEvent> $events
 * @property-read int|null $events_count
 */
class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'licenses';

    /**
     * @var list<string>
     */
    protected $hidden = [
        'license_key_hash',
        'encrypted_license_key',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'purchaser_user_id',
        'plan',
        'status',
        'purchaser_email',
        'license_key_hash',
        'encrypted_license_key',
        'license_key_suffix',
        'seat_count',
        'activation_limit',
        'updates_until',
        'expires_at',
        'provider',
        'provider_customer_id',
        'provider_checkout_id',
        'provider_subscription_id',
        'provider_metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => LicensePlan::class,
            'status' => LicenseStatus::class,
            'encrypted_license_key' => 'encrypted',
            'provider_metadata' => 'array',
            'updates_until' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function newFactory(): LicenseFactory
    {
        return LicenseFactory::new();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchaser_user_id');
    }

    /**
     * @return list<string>
     */
    public function features(): array
    {
        return $this->plan->features();
    }

    public function isUsable(): bool
    {
        if ($this->status !== LicenseStatus::Active) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isUpdateEligible(): bool
    {
        return $this->updates_until !== null && $this->updates_until->isFuture();
    }

    public function maskedLicenseKey(): string
    {
        $suffix = $this->license_key_suffix ?: substr((string) $this->getKey(), -6);

        return str_repeat('*', 24).$suffix;
    }

    public function hasRevealableLicenseKey(): bool
    {
        return $this->encrypted_license_key !== null;
    }

    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function activeActivations(): HasMany
    {
        $relation = $this->activations();
        $relation->whereNull('deactivated_at');

        return $relation;
    }

    public function events(): HasMany
    {
        return $this->hasMany(LicenseEvent::class);
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->where('status', LicenseStatus::Active->value)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
