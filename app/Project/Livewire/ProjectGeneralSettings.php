<?php

namespace App\Project\Livewire;

use App\Organization\Enums\OrganizationPermission;
use App\Project\Actions\UpdateProjectSettings;
use App\Project\Entities\ProjectStackData;
use App\Project\Entities\UpdateProjectSettingsPayload;
use App\Project\Enums\DeploymentProvider;
use App\Project\Models\Project;
use App\Project\Resolvers\ResolveProject;
use App\Project\Rules\ProjectRules;
use App\Project\Support\ProjectStackOptions;
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

    /**
     * Selected stack values.
     *
     * @var array{language: ?string, framework: ?string, platform: ?string}
     */
    public array $stack = [
        'language' => null,
        'framework' => null,
        'platform' => null,
    ];

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->projectId = $project->id;
        $this->name = $project->name;
        $this->description = $project->description;
        $this->deployment_provider = $project->deployment_provider;
        $this->stack = [
            'language' => $project->stack?->language?->value,
            'framework' => $project->stack?->framework?->value,
            'platform' => $project->stack?->platform?->value,
        ];
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

    #[Computed]
    public function languageOptions(): array
    {
        return ProjectStackOptions::languageOptions();
    }

    #[Computed]
    public function frameworkOptions(): array
    {
        $language = $this->stack['language'] ?? null;

        if ($language === null) {
            return [];
        }

        return ProjectStackOptions::frameworksFor($language);
    }

    #[Computed]
    public function platformOptions(): array
    {
        $language = $this->stack['language'] ?? null;

        if ($language === null) {
            return [];
        }

        return ProjectStackOptions::platformsFor($language);
    }

    public function updatedStackLanguage(?string $value): void
    {
        $this->stack['framework'] = null;
        $this->stack['platform'] = null;
        $this->deployment_provider = DeploymentProvider::OTHER;
    }

    public function updatedStackFramework(?string $value): void
    {
        $this->stack['platform'] = null;
        $this->deployment_provider = DeploymentProvider::OTHER;
    }

    public function updatedStackPlatform(?string $value): void
    {
        $this->deployment_provider = ProjectStackOptions::providerForPlatform($value);
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
                deploymentProvider: $validated['deployment_provider'],
                stack: isset($validated['stack'])
                    ? ProjectStackData::from($validated['stack'])
                    : null
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
