<?php

namespace App\Project\Livewire;

use App\Project\Models\Project;
use App\Project\Resolvers\ResolveProject;
use App\Secret\Livewire\SecretsManager;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class ProjectSecretsManager extends SecretsManager
{
    #[Locked]
    public string $projectId;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->projectId = $project->id;
        
        $this->setOwner($project);
    }

    #[Computed]
    public function project(): Project
    {
        return ResolveProject::onceWithContext($this->projectId);
    }

    public function render()
    {
        return view('project.project-secrets-manager');
    }
}
