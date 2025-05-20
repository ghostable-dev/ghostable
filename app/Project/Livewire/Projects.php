<?php

namespace App\Project\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Projects extends Component
{
    #[Computed()]
    public function projects(): LengthAwarePaginator
    {
        $team = auth()->user()->currentTeam();

        return $team->projects()->paginate();
    }

    public function render()
    {
        return view('project.projects-index');
    }
}
