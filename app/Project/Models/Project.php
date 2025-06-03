<?php

namespace App\Project\Models;

use App\Environment\Models\Environment;
use App\Team\Models\Team;
use App\Team\Models\TeamPermissionOverride;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_restricted'
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
    
    public function permissionOverrides(): MorphMany
    {
        return $this->morphMany(TeamPermissionOverride::class, 'target');
    }
    
    public function environmentOrFail(string $name): Environment
    {
        return $this->environments()
            ->where('name', $name)
            ->firstOrFail();
    }
    
    public function isRestricted(): bool
    {
        return $this->is_restricted;
    }
}
