<?php

namespace App\Account\Models;

use App\Account\Actions\CreateNonConflictingSlug;
use App\Core\Attributes\On;
use App\Core\Concerns\HandlesModelEventsWithAttributes;
use App\Project\Models\Project;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HandlesModelEventsWithAttributes;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'is_personal'
    ];
    
    protected $attributes = [
        'is_personal' => true
    ];

    public static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    #[On('creating')]
    public function handleCreatingEvent(Team $team): void
    {
        $team->slug = CreateNonConflictingSlug::handle(
            name: $team->name
        );
    }

    #[On('updating')]
    public function handleUpdateEvent(Team $team): void
    {
        if ($team->wasChanged('name')) {
            $team->slug = CreateNonConflictingSlug::handle(
                name: $team->name,
                existingTeam: $team
            );
        }
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_users');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
