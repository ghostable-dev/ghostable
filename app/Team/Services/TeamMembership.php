<?php

namespace App\Team\Services;

use App\Account\Models\User;
use App\Team\Models\Team;
use App\Team\Enums\TeamRole;
use App\Team\Enums\TeamPermission;
use Illuminate\Support\Facades\Cache;
use LogicException;

class TeamMembership
{
    /**
     * Create a new instance scoped to a specific user.
     */
    public function __construct(protected User $user) {}
    
    /**
     * Determine if the user belongs to the given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return Cache::memo()->rememberForever(
            $this->cacheKeyForMembership($team, $this->user),
            fn () => $team->users()->where('user_id', $this->user->id)->exists()
        );
    }
    
    /**
     * Determine if the user has the given role on the given team.
     */
    public function hasTeamRole(Team $team, TeamRole $role): bool
    {
        return $this->getMembershipForTeam($team)?->pivot->role === $role;
    }
    
    /**
     * Determine if the user has the given permission on the given team.
     */
    public function hasTeamPermission(Team $team, TeamPermission $permission): bool
    {
        return Cache::memo()->rememberForever(
            $this->cacheKeyForTeamPermission($team, $permission),
            fn () => $this->getMembershipForTeam($team)?->pivot->role?->hasPermission($permission) ?? false
        );
    }
    
    /**
     * Retrieve the user's membership pivot model for the given team.
     */
    public function getMembershipForTeam(Team $team): ?Team
    {
        return Cache::memo()->rememberForever(
            $this->cacheKeyForMembershipRecord($team, $this->user),
            fn () => $this->user->teams()->where('team_id', $team->id)->first()
        );
    }
    
    /**
     * Assign the user to the given team with the specified role.
     *
     * @throws LogicException if the user is already a member of the team.
     */
    public function assignToTeam(Team $team, TeamRole|string $role): void
    {
        if ($this->user->teams->contains($team)) {
            throw new LogicException('User is already a member of this team.');
        }

        $role = is_string($role) ? TeamRole::from($role) : $role;

        $this->user->teams()->attach($team, [
            'role' => $role->value,
            'permissions' => null,
        ]);

        $this->clearMembershipCache($team);
    }
    
    /**
     * Remove the user from the given team and clear related cache.
     */
    public function removeFromTeam(Team $team): void
    {
        if ($this->user->teams->contains($team->id)) {
            $this->user->teams()->detach($team->id);
            $this->clearMembershipCache($team);
        }
    }
    
    /**
     * Clear all cached membership data for the given team.
     */
    public function clearMembershipCache(Team $team): void
    {
        Cache::forget($this->cacheKeyForMembership($team));
        
        Cache::forget($this->cacheKeyForMembershipRecord($team));
    }
    
    /**
     * Generate the cache key for checking membership existence.
     */
    protected function cacheKeyForMembership(Team $team): string
    {
        return "team:{$team->id}:user:{$this->user->id}:belongs";
    }
    
    /**
     * Generate the cache key for storing the full membership record.
     */
    protected function cacheKeyForMembershipRecord(Team $team): string
    {
        return "teamMembership:{$team->id}:user:{$this->user->id}";
    }
    
    protected function cacheKeyForTeamPermission(Team $team, TeamPermission $permission): string
    {
        return "teamPermission:{$team->id}:user:{$this->user->id}:{$permission->value}";
    }
}