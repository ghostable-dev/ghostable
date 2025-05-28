<?php

namespace App\Project\Livewire;

use App\Project\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectView extends Component
{
    #[Locked]
    public string $projectId;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        
        $this->projectId = $project->id;
    }

    #[Computed()]
    public function project(): Project
    {
        return Project::findOrFail($this->projectId);
    }

    public function render()
    {
        return view('project.project-view');
    }
}
