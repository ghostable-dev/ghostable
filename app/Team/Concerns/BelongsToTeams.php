<?php

namespace App\Team\Concerns;

use App\Account\Entities\Role;
use App\Account\Enums\Permission;
use App\Account\Managers\ACLManager;
use App\Team\Models\Team;
use App\Team\Models\TeamUser;
use App\Account\Providers\ACLServiceProvider;
use App\Team\Actions\SwitchToTeam;
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
    
    public function roleForTeam(Team $team): ?Role
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
    
    public function hasTeamPermission(Permission $permission, Team $team): bool
    {
        $membership = $this->teams()
            ->where('team_id', $team->id)
            ->first();

        if (! $membership) {
            return false;
        }

        $role = $membership->pivot->role;
        $permissions = $membership->pivot->permissions ?? [];

        // 1. Admins always allowed
        if ($role->key === ACLServiceProvider::ROLE_ADMIN) {
            return true;
        }

        // 2. Custom role → check stored permissions
        if ($role->key === ACLServiceProvider::ROLE_CUSTOM) {
            return in_array($permission->value, $permissions);
        }

        // 3. Predefined role → check ACLManager-defined permissions
        if (! $role) {
            return false; // unknown role
        }

        return in_array($permission->value, $role->permissions);
    }
    
    public function isTeamAdmin(Team $team): bool
    {
        return $this->hasTeamRole(
            role: ACLManager::getRole(ACLServiceProvider::ROLE_ADMIN),
            team: $team
        );
    }
    
    public function hasTeamRole(Role $role, Team $team): bool
    {
        return $this->teams()
            ->where('teams.id', $team->id)
            ->wherePivot('role', $role->key)
            ->exists();
    }
   
    public function assignAsAdmin(Team $team): void
    {
        $this->assignToTeam(
            team: $team, 
            role: ACLManager::getRole(ACLServiceProvider::ROLE_ADMIN)
        );
    }
    
    public function assignAsBillingManager(Team $team): void
    {
        $this->assignToTeam(
            team: $team, 
            role: ACLManager::getRole(ACLServiceProvider::ROLE_BILLING_MANAGER)
        );
    }
    
    public function assignAsReadDeveloper(Team $team): void
    {
        $this->assignToTeam(
            team: $team, 
            role: ACLManager::getRole(ACLServiceProvider::ROLE_DEV_READ_ONLY)
        );
    }
    
    public function assignAsReadWriteDeveloper(Team $team): void
    {
        $this->assignToTeam(
            team: $team, 
            role: ACLManager::getRole(ACLServiceProvider::ROLE_DEV_READ_WRITE)
        );
    }
    
    public function assignToTeam(Team $team, Role|string $role, array $permissions = []): void
    {
        if ($this->teams->contains($team)) {
            throw new LogicException("User is already a member of this team.");
        }
        
        if (is_string($role)) {
            $resolved = ACLManager::getRole($role);

            if (! $resolved) {
                throw new InvalidArgumentException("The role '{$role}' is not defined.");
            }

            $role = $resolved;
        }

        $this->teams()->attach($team, [
            'role' => $role->key,
            'permissions' => $role->isCustom()
                ? array_map(fn($p) => $p instanceof Permission ? $p->value : $p, $permissions)
                : null,
        ]);
    }
}
