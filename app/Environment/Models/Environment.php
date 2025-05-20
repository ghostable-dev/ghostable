<?php

namespace App\Environment\Models;

use App\Project\Models\Project;
use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Environment extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
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
}
