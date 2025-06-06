<?php

namespace App\Team\Concerns;

use App\Environment\Models\Environment;
use App\Project\Models\Project;
use App\Team\Actions\SwitchToTeam;
use App\Team\Enums\TeamPermission;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use App\Team\Models\TeamPermissionOverride;
use App\Team\Models\TeamUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use LogicException;

trait BelongsToTeams
{
    public function currentTeam(): ?Team
    {
        $team = $this->teams()
            ->where('teams.id', $teamId = session('current_team_id'))
            ->first();

        if (! $team) {
            $personal = $this->personalTeam();
            app(SwitchToTeam::class)->handle($personal);

            return $personal;
        }

        return $team;
    }

    public function personalTeam(): Team
    {
        return $this->ownedTeams()->personal()->first();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->using(TeamUser::class)
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function roleForTeam(Team $team): ?TeamRole
    {
        $membership = $this->teams()
            ->where('team_id', $team->id)
            ->first();

        if (! $membership) {
            return null;
        }

        return $membership->pivot->role;
    }

    public function belongsToTeam(Team $team): bool
    {
        return $team->users()->where('user_id', $this->id)->exists();
    }

    public function removeFromTeam(Team $team): void
    {
        if ($this->teams->contains($team->id)) {
            $this->teams()->detach($team->id);
        }
    }

    public function hasTeamPermission(TeamPermission $permission, Team $team): bool
    {
        $membership = $this->teams()
            ->where('team_id', $team->id)
            ->first();

        if (! $membership) {
            return false;
        }

        $role = $membership->pivot->role;

        return $role->hasPermission($permission);
    }

    public function cans(
        TeamPermission $permission,
        ?Project $project = null,
        ?Environment $env = null
    ): bool {
        $team = $this->currentTeam();

        // 1. If project is restricted, override must explicitly allow
        if ($project?->is_restricted) {
            return TeamPermissionOverride::query()
                ->where('team_id', $team->id)
                ->where('user_id', $this->id)
                ->where('project_id', $project->id)
                ->where('permission', $permission->value)
                ->where('allowed', true)
                ->exists();
        }

        // 2. Check for any scoped override
        $override = TeamPermissionOverride::query()
            ->where('team_id', $team->id)
            ->where('user_id', $this->id)
            ->where('project_id', $project?->id)
            ->where('environment_id', $env?->id)
            ->where('permission', $permission->value)
            ->latest()
            ->first();

        return $override?->allowed ?? $this->teamRole()->has($permission);
    }

    public function isTeamAdmin(Team $team): bool
    {
        return $this->hasTeamRole(
            role: TeamRole::ADMIN,
            team: $team
        );
    }

    public function hasTeamRole(TeamRole $role, Team $team): bool
    {
        return $this->teams()
            ->where('teams.id', $team->id)
            ->wherePivot('role', $role->value)
            ->exists();
    }

    public function assignToTeam(Team $team, TeamRole|string $role): void
    {
        if ($this->teams->contains($team)) {
            throw new LogicException('User is already a member of this team.');
        }

        if (is_string($role)) {
            $resolved = TeamRole::from($role);

            if (! $resolved) {
                throw new InvalidArgumentException("The role '{$role}' is not defined.");
            }

            $role = $resolved;
        }

        $this->teams()->attach($team, [
            'role' => $role->value,
            'permissions' => null,
        ]);
    }
}
