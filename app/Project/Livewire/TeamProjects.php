<?php

namespace App\Project\Livewire;

use App\Team\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TeamProjects extends Component
{
    #[Computed]
    public function projects(): LengthAwarePaginator
    {
        return $this->team->projects()->paginate();
    }

    #[Computed(persist: true)]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }
    
    #[On('project-created')]
    public function refreshProjects(): void
    {
        $this->projects();
    }

    public function render()
    {
        return view('project.team-projects');
    }
}
