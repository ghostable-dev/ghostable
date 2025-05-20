<?php

namespace App\Project\Models;

use App\Account\Models\Team;
use App\Environment\Models\Environment;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
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
}
