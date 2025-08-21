<?php

namespace App\Environment\Variable\Models;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\LogVariableActivity;
use App\Environment\Variable\Builders\VariableBuilder;
use App\Environment\Variable\Casts\EncryptedVariableValue;
use App\Environment\Variable\Concerns\HasSecretValues;
use App\Environment\Versioning\Actions\CreateVariableVersion;
use App\Environment\Versioning\Models\EnvironmentVariableVersion;
use Database\Factories\EnvironmentVariableFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $environment_id
 * @property string $key
 * @property string $value
 * @property int $is_commented
 * @property int $is_override
 * @property int $is_deleted
 * @property \Illuminate\Support\Carbon|null $last_updated_at
 * @property string|null $last_updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Environment $environment
 * @property-read User|null $lastUpdatedBy
 * @property-read EnvironmentVariableVersion|null $latestVersion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EnvironmentVariableVersion> $versions
 * @property-read int|null $versions_count
 *
 * @method static VariableBuilder<static>|EnvironmentVariable commented()
 * @method static \Database\Factories\EnvironmentVariableFactory factory($count = null, $state = [])
 * @method static VariableBuilder<static>|EnvironmentVariable forEnvironment(\App\Environment\Models\Environment|string $environment)
 * @method static VariableBuilder<static>|EnvironmentVariable key(string $key)
 * @method static VariableBuilder<static>|EnvironmentVariable newModelQuery()
 * @method static VariableBuilder<static>|EnvironmentVariable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable onlyTrashed()
 * @method static VariableBuilder<static>|EnvironmentVariable overrides()
 * @method static VariableBuilder<static>|EnvironmentVariable query()
 * @method static VariableBuilder<static>|EnvironmentVariable recent(int $days = 7)
 * @method static VariableBuilder<static>|EnvironmentVariable visible()
 * @method static VariableBuilder<static>|EnvironmentVariable whereCreatedAt($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereDeletedAt($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereEnvironmentId($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereId($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereIsCommented($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereIsDeleted($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereIsOverride($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereKey($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereLastUpdatedAt($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereLastUpdatedBy($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereUpdatedAt($value)
 * @method static VariableBuilder<static>|EnvironmentVariable whereValue($value)
 * @method static VariableBuilder<static>|EnvironmentVariable withLatestVersion()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[UseEloquentBuilder(VariableBuilder::class)]
#[UseFactory(EnvironmentVariableFactory::class)]
class EnvironmentVariable extends Model
{
    use HasFactory;
    use HasSecretValues;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'key',
        'value',
        'is_commented',
        'is_override',
        'is_deleted',
        'last_updated_at',
        'last_updated_by',
    ];

    protected $casts = [
        'value' => EncryptedVariableValue::class,
        'last_updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (EnvironmentVariable $variable) {
            if ($variable->value !== null) {
                $variable->value = $variable->value;
            }
        });
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EnvironmentVariableVersion::class)
            ->orderBy('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(EnvironmentVariableVersion::class)
            ->orderByDesc('version');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Does the variable belong to the given environment (directly)
     */
    public function belongsToEnvironment(Environment $environment): bool
    {
        return $this->environment_id === $environment->id;
    }

    /**
     * Create a new version snapshot for this environment variable.
     *
     * This is a convenience wrapper around the CreateVariableVersion action,
     * allowing version creation to be triggered directly from the model.
     * Typically called after a change has been made to the variable's value
     * or metadata (e.g., is_commented).
     */
    public function createVersionBy(?User $user = null): EnvironmentVariableVersion
    {
        return app(CreateVariableVersion::class)->handle(
            variable: $this,
            changedBy: $user
        );
    }

    /**
     * Log an activity event related to this variable.
     *
     * This is a convenience wrapper around the LogVariableActivity action,
     * used to track user-initiated actions such as creation, updates,
     * deletion, or reveals.
     */
    public function logActivity(string $event, ?User $user = null): void
    {
        app(LogVariableActivity::class)->handle(
            variable: $this,
            event: $event,
            user: $user
        );
    }
}
