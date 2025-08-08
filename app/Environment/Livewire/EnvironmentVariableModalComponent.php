<?php

namespace App\Environment\Livewire;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Team\Enums\TeamPermission;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

abstract class EnvironmentVariableModalComponent extends Component
{
    /**
     * Event name used to trigger the variable editor modal.
     */
    public const LAUNCH = 'base:launch';

    /**
     * Indicates whether the variable editor modal is currently visible.
     */
    public bool $showing = false;

    /**
     * The ID of the environment variable currently being edited.
     */
    public ?string $environmentVariableId = null;

    /**
     * The ID of the target environment.
     */
    public ?string $targetEnvironmentId = null;

    /**
     * Livewire event listener to launch the environment variable modal.
     */
    #[On(self::LAUNCH)]
    public function launchModal(
        EnvironmentVariable $variable,
        ?Environment $targetEnvironment = null
    ): void {
        if (static::LAUNCH === 'base:launch') {
            throw new Exception('Subclasses must override the LAUNCH constant.');
        }

        $this->environmentVariableId = $variable->id;
        $this->targetEnvironmentId = $targetEnvironment?->id;
        $this->showing = true;
    }

    /**
     * Retrieve the variable instance based on the provided variable ID.
     */
    #[Computed]
    public function variable(): ?EnvironmentVariable
    {
        return EnvironmentVariable::find($this->environmentVariableId);
    }

    /**
     * Retrieve the target environment instance based on optionally provided environment ID.
     */
    #[Computed]
    public function targetEnvironment(): ?Environment
    {
        return $this->targetEnvironmentId
            ? ResolveEnvironment::onceWithContext($this->targetEnvironmentId)
            : $this->variable?->environment ?? null;
    }

    /**
     * Authorize the edit or override operation.
     */
    protected function authorizeEditOrOverride(): void
    {
        // The `targetEnvironment` represents the environment context in which the edit is occurring,
        // not necessarily the one that owns the variable. Regardless of whether the variable is
        // directly owned or inherited, the user must have permission to edit variables in the
        // target environment, since the edit (or override) will apply there.
        $this->authorize('perform', [$this->targetEnvironment, TeamPermission::EditVariables]);

        // If the variable is inherited, ensure it is actually from an ancestor of the target.
        // This prevents spoofing or tampering with unrelated variables by enforcing a valid
        // inheritance relationship.
        if (! $this->isEditingDirectVariable) {
            if (! $this->targetEnvironment->isDescendantOf($this->variable->environment)) {
                Log::warning('Blocked variable override attempt with invalid ancestry.', [
                    'user_id' => Auth::user()->id,
                    'target_env' => $this->targetEnvironment->id,
                    'variable_env' => $this->variable->environment->id,
                    'variable_key' => $this->variable->key,
                ]);
                abort(403, 'Invalid inheritance.');
            }
        }
    }

    /**
     * Is the variable owned by the target environment?
     */
    #[Computed()]
    public function isVariableOwnedByTargetEnvironment(): bool
    {
        return $this->variable?->belongsToEnvironment($this->targetEnvironment) ?? true;
    }

    /**
     * Is the variable owned by the target environment?
     */
    #[Computed]
    public function isOverride(): bool
    {
        return $this->variable?->is_override ?? false;
    }

    /**
     * Display a "success" toast.
     */
    protected function successToast(string $heading, string $text): void
    {
        Flux::toast(variant: 'success', heading: $heading, text: $text);
    }

    /**
     * Reset the modal state.
     */
    protected function closeAndReset(): void
    {
        $this->showing = false;

        $this->resetValues();
    }

    /**
     * Reset the modal values.
     */
    protected function resetValues(): void
    {
        $this->reset('environmentVariableId', 'targetEnvironmentId');
    }
}
