<?php

namespace App\Project\Livewire;

use App\Project\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectView extends Component
{
    #[Locked]
    public string $projectId;

    public string $tab = 'environments';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->projectId = $project->id;
    }

    /**
     * Refresh the project instance when a new environment is created
     * or when a project name/description is updated.
     */
    #[On(['environment-created', 'project-updated'])]
    public function updateEnvironments(): void
    {
        $this->project->refresh();
    }

    #[Computed]
    public function project(): Project
    {
        return Project::findOrFail($this->projectId);
    }

    public function render()
    {
        return view('project.project-view');
    }
}
