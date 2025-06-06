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

class Environment extends Model implements SupportsOverrides
{
    use HasFactory;
    use HasPermissionOverrides;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'is_restricted'
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
    
    public function owningTeam(): Team
    {
        return $this->project->team;
    }
}
