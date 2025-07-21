<?php

namespace App\Project\Models;

use App\Environment\Models\Environment;
use App\Project\Entities\ProjectNotificationsData;
use App\Team\Concerns\HasPermissionOverrides;
use App\Team\Contracts\SupportsOverrides;
use App\Team\Models\Team;
use App\Team\Resolvers\ResolveTeam;
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
 * @property string $team_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Environment> $environments
 * @property-read int|null $environments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Team\Models\TeamPermissionOverride> $permissionOverrides
 * @property-read int|null $permission_overrides_count
 * @property-read Team $team
 *
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 *
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
        'notifications' => ProjectNotificationsData::class,
    ];

    public static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }

    public function secrets(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Secret\Models\Secret::class, 'owner');
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

    public function owningTeam(): Team
    {
        return ResolveTeam::onceWithContext($this->team_id);
    }
}
