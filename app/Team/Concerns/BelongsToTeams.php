<?php

namespace App\Team\Concerns;

use App\Team\Actions\SwitchToTeam;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use App\Team\Models\TeamUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait BelongsToTeams
{
    public function currentTeam(): ?Team
    {
        return once(function () {
            $team = $this->teams()
                ->where('teams.id', $teamId = session('current_team_id'))
                ->first();

            if (! $team) {
                $team = $this->personalTeam();
                app(SwitchToTeam::class)->handle($team);
            }

            return $team;
        }, "currentTeam:{$this->id}");
    }

    public function personalTeam(): Team
    {
        return $this->ownedTeams()->personal()->first();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->using(TeamUser::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function isTeamAdmin(Team $team): bool
    {
        return $this->teamMembership()->hasTeamRole(
            role: TeamRole::ADMIN,
            team: $team
        );
    }
}
