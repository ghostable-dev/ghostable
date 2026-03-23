<?php

namespace App\Environment\Models;

use App\Auth\Models\PersonalAccessToken;
use App\Environment\Entities\EnvironmentNotificationsData;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Events\EnvironmentCreated;
use App\Environment\Events\EnvironmentDeleted;
use App\Environment\Events\EnvironmentUpdated;
use App\Organization\Concerns\HasPermissionOverrides;
use App\Organization\Contracts\SupportsOverrides;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationPermissionOverride;
use App\Project\Models\Project;
use App\Secret\Models\Secret;
use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\Contracts\TransformableData;

/**
 * @property string $id
 * @property int $is_restricted
 * @property string $project_id
 * @property string $name
 * @property EnvironmentType $type
 * @property EnvFileFormat $file_format
 * @property BaseData|TransformableData|null $notifications
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Collection<int, OrganizationPermissionOverride> $permissionOverrides
 * @property-read int|null $permission_overrides_count
 * @property-read Project $project
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\EnvironmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereFileFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereIsRestricted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereKekSalt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereNotifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Environment extends Model implements SupportsOverrides
{
    use HasApiTokens;
    use HasFactory;
    use HasPermissionOverrides;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'file_format',
        'is_restricted',
        'name',
        'notifications',
        'type',
    ];

    protected $casts = [
        'file_format' => EnvFileFormat::class,
        'notifications' => EnvironmentNotificationsData::class.':default',
        'type' => EnvironmentType::class,
    ];

    // protected static function booted(): void
    // {
    //     static::creating(function (Environment $environment) {
    //         $environment->kek_salt ??= base64_encode(
    //             Encrypter::generateKey(config('app.cipher')),
    //         );
    //     });
    // }

    protected $dispatchesEvents = [
        'created' => EnvironmentCreated::class,
        'updated' => EnvironmentUpdated::class,
        'deleted' => EnvironmentDeleted::class,
    ];

    public static function newFactory(): EnvironmentFactory
    {
        return EnvironmentFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function envSecrets(): HasMany
    {
        return $this->hasMany(EnvironmentSecret::class, 'environment_id');
    }

    public function secrets(): HasMany
    {
        return $this->hasMany(Secret::class, 'environment_id');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(EnvironmentKey::class, 'environment_id');
    }

    public function deploymentTokens(): HasMany
    {
        return $this->hasMany(DeploymentToken::class, 'environment_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('environment')
            ->logFillable()
            ->logOnlyDirty(true);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Created environment "'.$this->name.'"',
            'updated' => $this->wasChanged('name')
                ? 'Renamed environment from "'.$this->getOriginal('name').'" to "'.$this->name.'"'
                : 'Updated environment "'.$this->name.'"',
            'deleted' => 'Deleted environment "'.$this->name.'"',
            default => ucfirst($eventName).' environment "'.$this->name.'"',
        };
    }

    public function owningOrganization(): Organization
    {
        return once(function () {
            return $this->project->owningOrganization();
        }, "owningOrganization:{$this->id}");
    }
}
