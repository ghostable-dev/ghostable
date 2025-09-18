<?php

namespace App\Secret\Models;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Secret\Actions\LogSecretActivity;
use App\Secret\Casts\EncryptedSecretValue;
use App\Secret\Concerns\HasMaskedValue;
use App\Secret\Entities\SecretNotificationsData;
use App\Secret\Enums\SecretType;
use App\Secret\Versioning\Actions\CreateSecretVersion;
use App\Secret\Versioning\Models\SecretVersion;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $name
 * @property SecretType $type
 * @property string $value
 * @property string|null $dek_wrapped
 * @property string|null $kek_salt
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $last_updated_at
 * @property string|null $last_updated_by
 * @property string $created_by_id
 * @property \Spatie\LaravelData\Contracts\BaseData|\Spatie\LaravelData\Contracts\TransformableData|null $notifications
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $createdBy
 * @property-read Environment $environment
 * @property-read User|null $lastUpdatedBy
 * @property-read SecretVersion|null $latestVersion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SecretVersion> $versions
 * @property-read int|null $versions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereDekWrapped($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereEnvironmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereKekSalt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereLastUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereLastUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereNotifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Secret withoutTrashed()
 * @mixin \Eloquent
 */
class Secret extends Model
{
    use HasFactory;
    use HasMaskedValue;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'environment_id',
        'last_updated_at',
        'last_updated_by',
        'metadata',
        'name',
        'notifications',
        'type',
        'value',
        'dek_wrapped',
        'kek_salt',
    ];

    protected $casts = [
        'value' => EncryptedSecretValue::class,
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

    public function encrypter(): EncrypterContract
    {
        $environment = ResolveEnvironment::onceWithContext($this->environment_id);

        // @codeCoverageIgnoreStart
        if (! $environment) {
            throw new RuntimeException('Environment is missing; cannot resolve secret encrypter.');
        }
        // @codeCoverageIgnoreEnd

        if ($this->dek_wrapped) {
            $kek = $environment->encrypter($this->kek_salt);
            $dek = $kek->decryptString($this->dek_wrapped);

            return new Encrypter(base64_decode($dek), config('app.cipher'));
        }

        // Backward compatibility for secrets without a dedicated DEK
        return $environment->encrypter();
    }

    public function rotateDek(): void
    {
        $currentEncrypter = $this->encrypter();
        $plaintext = $currentEncrypter->decryptString($this->getRawOriginal('value'));

        $versionsPlain = [];
        foreach ($this->versions as $version) {
            $versionsPlain[$version->id] = $currentEncrypter->decryptString($version->getRawOriginal('value'));
        }

        $environment = ResolveEnvironment::onceWithContext($this->environment_id);
        $dek = base64_encode(Encrypter::generateKey(config('app.cipher')));
        $this->dek_wrapped = $environment->encrypter()->encryptString($dek);
        $this->kek_salt = $environment->kek_salt;
        $this->value = $plaintext; // re-encrypt with new DEK via cast
        $this->save();

        foreach ($this->versions as $version) {
            $version->value = $versionsPlain[$version->id];
            $version->save();
        }
    }
}
