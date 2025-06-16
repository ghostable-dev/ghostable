<?php

namespace App\Team\Models;

use App\Account\Models\User;
use App\Billing\Concerns\Billable;
use App\Core\Attributes\On;
use App\Core\Concerns\HandlesModelEventsWithAttributes;
use App\Project\Models\Project;
use App\Team\Actions\CreateNonConflictingSlug;
use App\Team\Builders\TeamBuilder;
use Database\Factories\TeamFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Team extends Model
{
    use Billable;
    use HandlesModelEventsWithAttributes;
    use HasFactory;
    use HasUuids;
    use LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'is_personal',
    ];

    protected $attributes = [
        'is_personal' => true,
    ];

    public static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    public function newEloquentBuilder($query): Builder
    {
        return new TeamBuilder($query);
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
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot(['role', 'permissions']);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('team')
            ->logOnly(['name'])
            ->logOnlyDirty(true);
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Created team "'.$this->name.'"',
            'updated' => $this->wasChanged('name')
                ? 'Renamed team from "'.$this->getOriginal('name').'" to "'.$this->name.'"'
                : 'Updated team "'.$this->name.'"',
            'deleted' => 'Deleted team "'.$this->name.'"',
            default => ucfirst($eventName).' team "'.$this->name.'"',
        };
    }

    public function isPersonal(): bool
    {
        return $this->is_personal;
    }
}
