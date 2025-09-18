<?php

namespace App\Project\Models;

use App\Environment\Models\Environment;
use App\Organization\Concerns\HasPermissionOverrides;
use App\Organization\Contracts\SupportsOverrides;
use App\Organization\Models\Organization;
use App\Organization\Resolvers\ResolveOrganization;
use App\Project\Entities\ProjectNotificationsData;
use App\Project\Events\ProjectCreated;
use App\Project\Events\ProjectDeleted;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property int $is_restricted
 * @property string $name
 * @property string|null $description
 * @property string $organization_id
 * @property \Spatie\LaravelData\Contracts\BaseData|\Spatie\LaravelData\Contracts\TransformableData|null $notifications
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Environment> $environments
 * @property-read int|null $environments_count
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Organization\Models\OrganizationPermissionOverride> $permissionOverrides
 * @property-read int|null $permission_overrides_count
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIsRestricted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereNotifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 * @mixin \Eloquent
 */
class Project extends Model implements SupportsOverrides
{
    use HasFactory;
    use HasPermissionOverrides;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_restricted',
        'notifications',
    ];

    protected $casts = [
        'notifications' => ProjectNotificationsData::class.':default',
    ];

    protected $dispatchesEvents = [
        'created' => ProjectCreated::class,
        'deleted' => ProjectDeleted::class,
    ];

    public static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project')
            ->logFillable()
            ->logOnlyDirty(true);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Created project "'.$this->name.'"',
            'updated' => $this->wasChanged('name')
                ? 'Renamed project from "'.$this->getOriginal('name').'" to "'.$this->name.'"'
                : 'Updated project "'.$this->name.'"',
            'deleted' => 'Deleted project "'.$this->name.'"',
            default => ucfirst($eventName).' project "'.$this->name.'"',
        };
    }

    public function environmentOrFail(string $name): Environment
    {
        return $this->environments()
            ->where('name', $name)
            ->firstOrFail();
    }

    public function owningOrganization(): Organization
    {
        return ResolveOrganization::onceWithContext($this->organization_id);
    }
}
