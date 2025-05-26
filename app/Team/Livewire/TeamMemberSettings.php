<?php

namespace App\Team\Livewire;

use App\Team\Actions\UpdateTeamMemberRole;
use App\Account\Managers\ACLManager;
use App\Team\Models\Team;
use App\Account\Models\User;
use Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamMemberSettings extends Component
{
    public ?string $managingRoleForUserId;
    public ?string $managingRole;
    public ?string $memberToBeDeletedId;
    
    public function mount(): void
    {}
    
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
    
    #[Computed()]
    public function managingRoleUser(): ?User
    {
        return User::find($this->managingRoleForUserId ?? null);
    }
    
    public function manageMemberRole(User $user): void
    {
        //$this->authorize('update', $user);
        
        $this->managingRoleForUserId = $user->id;
        $this->managingRole = $user->roleForTeam($this->team)?->key;
        
        Flux::modal('manage-member-role')->show();
    }
    
    public function saveMemberRole(): void
    {
        // $this->authorize('updateRole', $this->managingRoleUser);
        
        UpdateTeamMemberRole::handle(
            member: $this->managingRoleUser,
            team: $this->team,
            role: ACLManager::getRole($this->managingRole)
        );
        
        $this->reset(['managingRoleForUserId', 'managingRole']);
        
        Flux::toast('Team member role successfully updated.');
        $this->modal('manage-member-role')->close();
    }
    
    #[Computed()]
    public function memeberToBeDeleted(): ?User
    {
        return User::find($this->memberToBeDeletedId ?? null);
    }
    
    public function confirmRemoveMember(User $user): void
    {
        //$this->authorize('removeMembers', $this->team);
        
        $this->memberToBeDeletedId = $user->id;
        
        Flux::modal('remove-member')->show();
    }
    
    public function removeMember(): void
    {
        //$this->authorize('delete', $this->memeberToBeDeleted);
        
        $this->memeberToBeDeleted->removeFromTeam($this->team);
        
        $this->reset(['memberToBeDeletedId']);
        
        Flux::toast('Team member successfully removed.');
        
        $this->modal('remove-member')->close();
    }
    
    public function render()
    {
        return view('team.settings.member-settings');
    }
}
