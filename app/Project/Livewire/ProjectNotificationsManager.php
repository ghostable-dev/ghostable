<?php

namespace App\Project\Livewire;

use App\Project\Actions\UpdateProjectNotifications;
use App\Project\Entities\ProjectNotificationsData;
use App\Project\Models\Project;
use App\Project\Resolvers\ResolveProject;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectNotificationsManager extends Component
{
    #[Locked]
    public string $projectId;

    public function mount(Project $project): void
    {
        $this->projectId = $project->id;
    }

    #[Computed]
    public function project(): Project
    {
        return ResolveProject::onceWithContext($this->projectId);
    }

    public function toggle(string $key): void
    {
        $data = $this->project->notifications->toArray();
        $data[$key] = ! ($data[$key] ?? false);

        app(UpdateProjectNotifications::class)->handle(
            project: $this->project,
            data: ProjectNotificationsData::from($data)
        );

        $this->project->refresh();
    }

    public function render()
    {
        return view('project.project-notifications-manager');
    }
}
