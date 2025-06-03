<?php

namespace App\Project\Livewire;

use App\Project\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectGeneralSettings extends Component
{
    #[Locked]
    public string $projectId;
    
    public string $name;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->projectId = $project->id;
        $this->name = $project->name;
    }

    #[Computed]
    public function project(): Project
    {
        return Project::findOrFail($this->projectId);
    }
    
    #[Computed(persist: true)]
    public function canEditName(): bool
    {
        return request()->user()->can('manage', $this->project);
    }
    
    public function updateName(): void
    {
        $this->authorize('manage', $this->project);

        $this->project->update(['name' => $this->name]);

        $this->dispatch('name-updated', name: $this->name);
    }

    public function render()
    {
        return view('project.project-general-settings');
    }
}
