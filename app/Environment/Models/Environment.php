<?php

namespace App\Environment\Models;

use App\Environment\Enums\EnvironmentType;
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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Environment extends Model implements SupportsOverrides
{
    use HasFactory;
    use HasPermissionOverrides;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'is_restricted',
    ];

    protected $casts = [
        'type' => EnvironmentType::class,
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
        return $this->project->team;
    }

    public function findVariableForKey(string $key): ?EnvironmentVariable
    {
        return $this->variables()->where('key', $key)->first();
    }
}
