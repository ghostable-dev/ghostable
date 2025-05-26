<?php

namespace App\Team\Livewire;

use App\Team\Actions\CreateTeamInvite;
use App\Account\Managers\ACLManager;
use App\Team\Models\Team;
use App\Account\Providers\ACLServiceProvider;
use Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InviteSender extends Component
{
    public string $emailToInvite = '';
    
    public string $roleToInvite = ACLServiceProvider::ROLE_DEV_READ_ONLY;

    public function mount(): void
    {}
    
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

    public function render()
    {
        return view('team.settings.invite-sender');
    }
}
