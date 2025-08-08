<?php

namespace App\Environment\Versioning\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Variable\Actions\LogVariableRevealed;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Versioning\Actions\RestoreVariableVersion;
use App\Environment\Versioning\Models\EnvironmentVariableVersion;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class VersionManager extends Component
{
    use ConfirmsPasswords;

    /**
     * Whether the version manager UI is currently visible.
     */
    public bool $showing = false;

    /**
     * The ID of the EnvironmentVariable being managed.
     */
    public ?string $environmentVariableId = null;

    /**
     * Tracks which version IDs have their secret values revealed.
     *
     * @var array<int,bool>
     */
    #[Locked]
    public array $showingValues = [];

    /**
     * Event name to trigger launching the version manager.
     */
    public const LAUNCH = 'version-manager:launch';

    /**
     * Event name emitted after a version has been restored.
     */
    public const VERSION_RESTORED = 'version-manager:version-restored';

    /**
     * Launch the version manager for the given environment variable.
     *
     * Sets the selected variable ID and opens the version manager UI.
     */
    #[On(self::LAUNCH)]
    public function launch(EnvironmentVariable $variable): void
    {
        $this->environmentVariableId = $variable->id;

        $this->showing = true;
    }

    /**
     * Get the currently selected EnvironmentVariable model.
     */
    #[Computed]
    public function variable(): ?EnvironmentVariable
    {
        return EnvironmentVariable::find($this->environmentVariableId);
    }

    /**
     * Retrieve all version records for the selected variable, ordered descending.
     *
     * @return Collection<int, EnvironmentVariableVersion>
     */
    #[Computed]
    public function versions(): Collection
    {
        if (! $this->variable) {
            return collect();
        }

        return $this->variable->versions()
            ->reorder('version', 'desc')
            ->get();
    }

    /**
     * Restore the environment variable to the specified version.
     *
     * Performs authorization, executes the restore action, shows a success toast,
     * emits activity update events, and closes the version manager UI.
     */
    public function restoreToVersion(EnvironmentVariableVersion $version): void
    {
        $this->authorize('perform', [$version->variable->environment, TeamPermission::EditVariables]);

        app(RestoreVariableVersion::class)->handle(
            version: $version,
            restoredBy: Auth::user()
        );

        Flux::toast(
            variant: 'success',
            heading: 'Version Restored',
            text: "“{$this->variable->key}” was successfully restored."
        );

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->dispatch(self::VERSION_RESTORED);

        $this->showing = false;
    }

    /**
     * Toggle visibility of a secret value for a specific version.
     *
     * Authorizes the action, flips the visibility flag, logs the reveal if shown,
     * and emits an activity update event.
     */
    public function toggleSecret(EnvironmentVariableVersion $version): void
    {
        $this->authorize('perform', [$version->variable->environment, TeamPermission::EditVariables]);

        $isNowVisible = ! ($this->showingValues[$version->id] ?? false);

        $this->showingValues[$version->id] = $isNowVisible;

        if ($isNowVisible) {
            app(LogVariableRevealed::class)->handle($version->variable);
            $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        }
    }

    public function render()
    {
        return view('environment.versioning.version-manager');
    }
}
