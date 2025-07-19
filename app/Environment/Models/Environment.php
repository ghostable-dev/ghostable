<?php

namespace App\Environment\Models;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Project\Models\Project;
use App\Team\Concerns\HasPermissionOverrides;
use App\Team\Contracts\SupportsOverrides;
use App\Team\Models\Team;
use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property int $is_restricted
 * @property string $project_id
 * @property string $name
 * @property EnvironmentType $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Team\Models\TeamPermissionOverride> $permissionOverrides
 * @property-read int|null $permission_overrides_count
 * @property-read Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Environment\Models\EnvironmentVariable> $variables
 * @property-read int|null $variables_count
 *
 * @method static \Database\Factories\EnvironmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereIsRestricted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment withTrashed()
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
        'name',
        'type',
        'is_restricted',
        'file_format',
    ];

    protected $casts = [
        'type' => EnvironmentType::class,
        'file_format' => EnvFileFormat::class,
    ];

    public static function newFactory(): EnvironmentFactory
    {
        return EnvironmentFactory::new();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(EnvironmentVariable::class);
    }

    public function secrets(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Secret\Models\Secret::class, 'owner');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(EnvironmentVariableRule::class);
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

    public function owningTeam(): Team
    {
        return once(function () {
            return $this->project->owningTeam();
        }, "owningTeam:{$this->id}");
    }

    public function findVariableForKey(string $key): ?EnvironmentVariable
    {
        return $this->variables()->where('key', $key)->first();
    }
}
