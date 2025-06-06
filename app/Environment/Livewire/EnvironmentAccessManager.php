<?php

namespace App\Environment\Livewire;

use App\Account\Models\User;
use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Models\Environment;
use App\Team\Actions\CreatePermissionOverride;
use App\Team\Enums\TeamPermission;
use App\Team\Models\TeamPermissionOverride;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EnvironmentAccessManager extends Component
{
    use ConfirmsPasswords;

    #[Locked]
    public string $environmentId;

    /**
     * Indicates whether access to the current environment is restricted to
     * explicitly assigned permission overrides.
     *
     * When true, default team roles are ignored for non-admins.
     */
    public bool $is_restricted;

    /**
     * The ID of the team member currently selected for assigning
     * permission overrides on the project.
     *
     * Used to scope override actions to a specific user.
     */
    public ?string $userId = null;

    /**
     * The permission being granted in the current override action.
     */
    public TeamPermission $permission;

    /**
     * The ID of the permission override currently selected for removal.
     *
     * Used to resolve the override instance prior to deletion.
     */
    public ?string $overrideToRemoveId = null;

    public function mount(Environment $environment): void
    {
        $this->authorize('manageAccessControls', $environment->project->team);

        $this->environmentId = $environment->id;
        $this->is_restricted = $environment->is_restricted;
    }

    /**
     * Retrieve the environment instance based on the bound environment ID.
     */
    #[Computed]
    public function environment(): Environment
    {
        return Environment::findOrFail($this->environmentId);
    }

    /**
     * Revert the `is_restricted` value back to its original state from the environment,
     * effectively canceling any unsaved change made in the UI.
     *
     * Closes the confirmation modal without saving.
     */
    public function cancelIsRestrictedChange(): void
    {
        $this->is_restricted = $this->environment->is_restricted;

        Flux::modal('confirm-restricted-access')->close();
    }

    /**
     * Persist the updated `is_restricted` value to the environment after confirmation.
     *
     * Ensures the user is authorized to perform the change,
     * then saves the new state, closes the modal,
     * and shows a confirmation toast.
     */
    public function updateIsRestricted(): void
    {
        $this->authorize('manageAccessControls', $this->environment->project->team);

        $this->environment->update(['is_restricted' => $this->is_restricted]);

        Flux::modal('confirm-restricted-access')->close();
        Flux::toast('Environment access updated.');
    }

    /**
     * Get a paginated list of permission overrides for the current environment.
     *
     * This includes all custom permission assignments that override default
     * team roles for non-admin users on this environment.
     *
     * @return LengthAwarePaginator<TeamPermissionOverride>
     */
    #[Computed]
    public function overrides(): LengthAwarePaginator
    {
        return $this->environment->permissionOverrides()->paginate(20);
    }

    /**
     * Get the list of team members eligible for permission overrides
     * on the current environment, excluding admins.
     *
     * @return Collection<User>
     */
    #[Computed(persist: true)]
    public function members(): Collection
    {
        return $this->environment->project->team->users
            ->reject(function (User $user) {
                return $user->isTeamAdmin($this->environment->project->team);
            });
    }

    /**
     * Get the list of team permissions that can still be assigned
     * to the selected overriding member for the current environment.
     * This excludes any permissions that have already been overridden.
     *
     * If no overriding user is selected, returns the full
     * set of environment-level override permissions.
     *
     * @return array<TeamPermission>
     */
    #[Computed]
    public function assignablePermissions(): array
    {
        if (! $this->userId) {
            return TeamPermission::environmentOverrides();
        }

        $assigned = $this->environment->permissionOverrides()
            ->forUser($this->overridingMember)
            ->pluck('permission');

        return collect(TeamPermission::environmentOverrides())
            ->reject(fn ($permission) => $assigned->contains($permission))
            ->values()
            ->all();
    }

    /**
     * Get the user on the current environment’s team whose
     * permissions are being overridden.
     *
     * This is resolved from the selected 'userId'
     * and scoped to ensure the user is a member of the
     * environment’s associated team.
     */
    #[Computed]
    public function overridingMember(): User
    {
        return $this->environment->project->team->users()
            ->where('user_id', $this->userId)
            ->first();
    }

    /**
     * Add a new permission override for the selected
     * user on the current environment.
     *
     * After a successful override, it resets input state, shows
     * a toast message, and closes the modal.
     */
    public function createOverride(): void
    {
        $this->authorize('manageAccessControls', $this->environment->project->team);

        app(CreatePermissionOverride::class)->handle(
            user: $this->overridingMember,
            target: $this->environment,
            permission: $this->permission
        );

        $this->reset(['userId', 'permission']);

        Flux::toast('Custom permission override added successfully.');
        Flux::modal('add-override')->close();
    }

    /**
     * Begin the override removal process by storing the override ID
     * and showing the confirmation modal.
     */
    public function confirmOverrideRemoval(TeamPermissionOverride $override): void
    {
        $this->overrideToRemoveId = $override->id;

        $team = $this->overrideToRemove->target->project->team;

        $this->authorize('manageAccessControls', $team);

        Flux::modal('confirm-override-removal')->show();
    }

    /**
     * Resolve the permission override that is pending removal.
     *
     * Returns the override based on the stored ID, or null if not set.
     */
    #[Computed]
    public function overrideToRemove(): ?TeamPermissionOverride
    {
        return TeamPermissionOverride::find($this->overrideToRemoveId);
    }

    /**
     * Permanently delete the selected permission override.
     *
     * After deletion, the confirmation modal is closed.
     */
    public function removeOverride(): void
    {
        $team = $this->overrideToRemove->target->project->team;

        $this->authorize('manageAccessControls', $team);

        $this->overrideToRemove->delete();

        Flux::modal('confirm-override-removal')->close();
    }

    public function render()
    {
        return view('environment.environment-access-manager');
    }
}
