<?php

namespace App\Project\Models;

use App\Environment\Models\Environment;
use App\Team\Concerns\HasPermissionOverrides;
use App\Team\Contracts\SupportsOverrides;
use App\Team\Models\Team;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        return $this->team;
    }
}
