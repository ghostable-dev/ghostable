<?php

namespace App\Project\Livewire;

use App\Team\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamProjects extends Component
{
    public function mount(): void
    {
        $this->authorize('view', $this->team);
    }

    #[Computed()]
    public function projects(): LengthAwarePaginator
    {
        return $this->team->projects()->paginate();
    }

    #[Computed(persist: true)]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }

    public function render()
    {
        return view('project.team-projects');
    }
}
