<?php

namespace App\Team\Livewire;

use App\Team\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamMemberSettings extends Component
{
    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }

    public function render()
    {
        return view('team.team-member-settings');
    }
}
