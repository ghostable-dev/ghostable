<?php

namespace App\Environment\Models;

use App\Account\Models\User;
use App\Environment\Actions\LogVariableActivity;
use App\Environment\Casts\EncryptedString;
use App\Environment\Concerns\HasSecretValues;
use App\Environment\Versioning\Actions\CreateVariableVersion;
use App\Environment\Versioning\Models\EnvironmentVariableVersion;
use Database\Factories\EnvironmentVariableFactory;
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
 * @property \Illuminate\Support\Carbon|null $last_updated_at
 * @property string|null $last_updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Environment\Models\Environment $environment
 * @property-read User|null $lastUpdatedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Environment\Versioning\Models\EnvironmentVariableVersion> $versions
 * @property-read int|null $versions_count
 *
 * @method static \Database\Factories\EnvironmentVariableFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereEnvironmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereIsCommented($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereLastUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereLastUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EnvironmentVariable withoutTrashed()
 *
 * @property-read \App\Environment\Versioning\Models\EnvironmentVariableVersion|null $latestVersion
 *
 * @mixin \Eloquent
 */
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
        'last_updated_at',
        'last_updated_by',
    ];

    protected $casts = [
        'value' => EncryptedString::class,
        'last_updated_at' => 'datetime',
    ];

    public static function newFactory(): EnvironmentVariableFactory
    {
        return EnvironmentVariableFactory::new();
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
            ->latestOfMany();
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
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
