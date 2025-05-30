<?php

namespace App\Team\Livewire;

use App\Account\Models\User;
use App\Team\Actions\UpdateTeamMemberRole;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamMembersManager extends Component
{
    public ?string $managingRoleForUserId;

    public ?TeamRole $managingRole;

    public ?string $memberToBeDeletedId;

    #[Computed()]
    public function members(): LengthAwarePaginator
    {
        return $this->team->users()->paginate(10);
    }

    #[Computed()]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }

    #[Computed]
    public function managingRoleUser(): ?User
    {
        $user = User::find($this->managingRoleForUserId ?? null);

        if (! $user) {
            return null;
        }

        // Enforce that the user is actually on the team
        if (! $user->belongsToTeam($this->team)) {
            return null;
        }

        return $user;
    }

    public function manageMemberRole(User $user): void
    {
        $this->authorize('manageMembers', $this->team);

        $this->managingRoleForUserId = $user->id;
        $this->managingRole = $user->roleForTeam($this->team);

        Flux::modal('manage-member-role')->show();
    }

    public function saveMemberRole(): void
    {
        $this->authorize('manageMembers', $this->team);

        UpdateTeamMemberRole::handle(
            member: $this->managingRoleUser,
            team: $this->team,
            role: $this->managingRole
        );

        $this->reset(['managingRoleForUserId', 'managingRole']);

        Flux::toast('Team member role successfully updated.');
        $this->modal('manage-member-role')->close();
    }

    #[Computed()]
    public function memberToBeDeleted(): ?User
    {
        $user = User::find($this->memberToBeDeletedId ?? null);
        if (! $user) {
            return null;
        }

        // Enforce that the user is actually on the team
        if (! $user->belongsToTeam($this->team)) {
            return null;
        }

        return $user;
    }

    public function confirmRemoveMember(User $user): void
    {
        $this->authorize('manageMembers', $this->team);

        $this->memberToBeDeletedId = $user->id;

        Flux::modal('remove-member')->show();
    }

    public function removeMember(): void
    {
        $this->authorize('manageMembers', $this->team);

        $this->memberToBeDeleted->removeFromTeam($this->team);

        $this->reset(['memberToBeDeletedId']);

        Flux::toast('Team member successfully removed.');

        $this->modal('remove-member')->close();
    }

    public function render()
    {
        return view('team.team-members-manager');
    }
}
