<?php

namespace App\Secret\Models;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Secret\Actions\LogSecretActivity;
use App\Secret\Concerns\HasMaskedValue;
use App\Secret\Entities\SecretNotificationsData;
use App\Secret\Enums\SecretType;
use App\Secret\Versioning\Actions\CreateSecretVersion;
use App\Secret\Versioning\Models\SecretVersion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $name
 * @property SecretType $type
 * @property string $value_encrypted
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $last_updated_at
 * @property string|null $last_updated_by
 * @property string $created_by_id
 * @property \Spatie\LaravelData\Contracts\BaseData|\Spatie\LaravelData\Contracts\TransformableData|null $notifications
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $createdBy
 * @property-read User|null $lastUpdatedBy
 * @property-read SecretVersion|null $latestVersion
 * @property-read Environment $environment
 * @property mixed $value
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SecretVersion> $versions
 * @property-read int|null $versions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereLastUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereLastUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereNotifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereEnvironmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereValueEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Secret extends Model
{
    use HasFactory;
    use HasMaskedValue;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'value_encrypted',
        'metadata',
        'notifications',
        'last_updated_at',
        'last_updated_by',
    ];

    protected $casts = [
        'type' => SecretType::class,
        'metadata' => 'array',
        'last_updated_at' => 'datetime',
        'notifications' => SecretNotificationsData::class,
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SecretVersion::class)
            ->orderBy('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(SecretVersion::class)
            ->orderByDesc('version');
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->value_encrypted) {
                    return null;
                }

                $encrypter = $this->environment->encrypter();

                try {
                    return $encrypter->decryptString($this->value_encrypted);
                } catch (\Throwable $e) {
                    // Fallback to application key for legacy data
                    try {
                        return app('encrypter')->decryptString($this->value_encrypted);
                    } catch (\Throwable $e2) {
                        Log::warning('Secret decryption failed', [
                            'secret_id' => $this->id,
                            'exception_class' => get_class($e2),
                            'exception_msg' => $e2->getMessage(),
                        ]);

                        return null;
                    }
                }
            },
            set: function ($value) {
                if ($value === null) {
                    return ['value_encrypted' => null];
                }

                return [
                    'value_encrypted' => $this->environment
                        ->encrypter()
                        ->encryptString($value),
                ];
            },
        );
    }

    public function displayValue(): string
    {
        return str_repeat('•', 10);
    }

    public function createVersionBy(?User $user = null): SecretVersion
    {
        return app(CreateSecretVersion::class)->handle(
            secret: $this,
            changedBy: $user,
        );
    }

    public function logActivity(string $event, ?User $user = null): void
    {
        app(LogSecretActivity::class)->handle(
            secret: $this,
            event: $event,
            user: $user,
        );
    }
}
