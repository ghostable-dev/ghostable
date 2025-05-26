<?php

namespace App\Project\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamProjects extends Component
{
    #[Computed()]
    public function projects(): LengthAwarePaginator
    {
        $team = Auth::user()->currentTeam();

        return $team->projects()->paginate();
    }

    public function render()
    {
        return view('project.team-projects');
    }
}
