<?php

namespace App\Project\Livewire;

use App\Organization\Enums\OrganizationPermission;
use App\Project\Actions\UpdateProjectSettings;
use App\Project\Entities\UpdateProjectSettingsPayload;
use App\Project\Enums\DeploymentProvider;
use App\Project\Models\Project;
use App\Project\Resolvers\ResolveProject;
use App\Project\Rules\ProjectRules;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectGeneralSettings extends Component
{
    #[Locked]
    public string $projectId;

    public bool $is_zero_knowledge = false;

    /**
     * The editable name of the project.
     */
    public string $name;

    /**
     * An optional description for the project.
     */
    public ?string $description;

    /**
     * The deployment provider for the given project.
     */
    public DeploymentProvider $deployment_provider;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->projectId = $project->id;
        $this->name = $project->name;
        $this->description = $project->description;
        $this->deployment_provider = $project->deployment_provider;
        $this->is_zero_knowledge = ! $project->is_legacy;
    }

    /**
     * Retrieve the current project instance based on the bound project ID.
     */
    #[Computed]
    public function project(): Project
    {
        return ResolveProject::onceWithContext($this->projectId);
    }

    #[Computed(persist: true)]
    public function deploymentProviders(): array
    {
        return DeploymentProvider::cases();
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
        return Gate::allows('perform', [$this->project, OrganizationPermission::ManageProjectSettings]);
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
        $this->authorize('perform', [$this->project, OrganizationPermission::ManageProjectSettings]);

        $validated = $this->validate(ProjectRules::updateRules($this->project));

        resolve(UpdateProjectSettings::class)->handle(
            project: $this->project,
            payload: new UpdateProjectSettingsPayload(
                name: $validated['name'],
                description: $validated['description'],
                deploymentProvider: $validated['deployment_provider']
            )
        );

        $this->project->refresh();

        $this->dispatch('project-updated');
    }

    public function updateLegacy(): void
    {
        $this->authorize('perform', [$this->project, OrganizationPermission::ManageProjectSettings]);

        $this->project->is_legacy = ! $this->is_zero_knowledge;
        $this->project->save();

        $this->project->refresh();

        $this->dispatch('legacy-updated');
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
