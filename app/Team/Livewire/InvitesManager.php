<?php

namespace App\Team\Livewire;

use App\Team\Actions\CreateTeamInvite;
use App\Account\Managers\ACLManager;
use App\Team\Models\Team;
use App\Team\Models\TeamInvite;
use App\Account\Providers\ACLServiceProvider;
use Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InvitesManager extends Component
{
    public ?string $inviteToDeleteId;
    
    public string $emailToInvite = '';
    
    public string $roleToInvite = ACLServiceProvider::ROLE_DEV_READ_ONLY;

    public function mount(): void
    {}
    
    #[Computed(persist: false)]
    public function pendingInvites(): Collection
    {
        return $this->team->invites()->pending()->get();
    }
    
    #[Computed(persist: false)]
    public function inviteToBeDeleted(): ?TeamInvite
    {
        return TeamInvite::find($this->inviteToDeleteId ?? null);
    }

    #[Computed()]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }
    
    public function createInvite()
    {
        //$this->authorize('admin', $this->account);
        
        CreateTeamInvite::handle(
            team: $this->team,
            user: Auth::user(),
            email: $this->emailToInvite,
            role: ACLManager::getRole($this->roleToInvite)
        );
        
        $this->modal('create-invite')->close();
        
        $this->reset(['emailToInvite', 'roleToInvite']);
        
        Flux::toast('Invite sent to team member.');
    }
    
    public function resendInvite(TeamInvite $invite): void
    {
        //$this->authorize('admin', $invite->account);
        
        if ($invite->sentRecently()) {
            Flux::toast('Please wait a few minutes before resending.');
            return;
        } else {
            $invite->send();
        }
        
        Flux::toast(
            heading: 'Invite has been resent.',
            text: 'Please allow a few minutes for your email to be delivered.',
        );
    }
    
    public function confirmDeleteInvite(TeamInvite $invite): void
    {
        //$this->authorize('admin', $invite->account);
        
        $this->inviteToDeleteId = $invite->id;
        
        Flux::modal('delete-invite')->show();
    }
    
    public function deleteInvite(TeamInvite $invite): void
    {
        //$this->authorize('admin', $invite->account);
        
        $invite->delete();
        
        $this->reset(['inviteToDeleteId']);
        
        $this->modal('delete-invite')->close();
        
        Flux::toast('The invite has been deleted.');
    }
    
    public function render()
    {
        return view('team.settings.invites-manager');
    }
}
