<?php

namespace App\Project\Livewire;

use App\Project\Models\Project;
use App\Project\Rules\ProjectRules;
use App\Team\Enums\TeamPermission;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectGeneralSettings extends Component
{
    #[Locked]
    public string $projectId;

    /**
     * The editable name of the project.
     */
    public string $name;

    /**
     * An optional description for the project.
     */
    public ?string $description;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->projectId = $project->id;
        $this->name = $project->name;
        $this->description = $project->description;
    }

    /**
     * Retrieve the current project instance based on the bound project ID.
     */
    #[Computed]
    public function project(): Project
    {
        return Project::findOrFail($this->projectId);
    }

    /**
     * Determine whether the authenticated user can edit project settings.
     *
     * Uses the ManageProjectSettings permission to authorize access.
     * This value is persisted to optimize reactive checks.
     */
    #[Computed(persist: true)]
    public function canEdit(): bool
    {
        return Gate::allows('perform', [$this->project, TeamPermission::ManageProjectSettings]);
    }

    /**
     * Update the current project's name and description.
     *
     * This method:
     * - Authorizes the user with the ManageProjectSettings permission
     * - Validates the input using project-specific update rules
     * - Applies the update to the project
     * - Dispatches a 'project-updated' event for UI updates
     */
    public function updateProject(): void
    {
        $this->authorize('perform', [$this->project, TeamPermission::ManageProjectSettings]);

        $validated = $this->validate(ProjectRules::updateRules($this->project));

        $this->project->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        $this->dispatch('project-updated');
    }

    /**
     * Permanently delete the current project.
     *
     * This method:
     * - Authorizes the user using the project-level 'delete' policy
     * - Deletes the project and all related environments, variables, and overrides
     * - Redirects the user to the dashboard after deletion
     */
    public function deleteProject(): void
    {
        $this->authorize('delete', $this->project);

        $this->project->delete();

        $this->redirect(route('dashboard'));
    }

    public function render()
    {
        return view('project.project-general-settings');
    }
}
