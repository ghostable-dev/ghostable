<?php

namespace App\Environment\Variable\Livewire;

use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\DeleteVariable;
use App\Environment\Variable\Actions\SuppressInheritedVariable;
use App\Environment\Variable\Actions\SuppressOverrideVariable;
use App\Environment\Variable\Models\EnvironmentVariable;
use Auth;
use Livewire\Attributes\On;

class VariableDeleter extends VariableModalComponent
{
    public string $deleteMode = 'delete';

    /**
     * Events
     */
    public const LAUNCH = 'variable-deleter:launch';

    public const DELETED = 'variable-deleted:deleted';

    /**
     * Livewire event listener to launch the environment variable modal.
     */
    #[On(self::LAUNCH)]
    public function launchModal(EnvironmentVariable $variable, ?Environment $targetEnvironment = null): void
    {
        $this->authorizeEnvironment(variable: $variable, target: $targetEnvironment);

        parent::launchModal($variable, $targetEnvironment);
    }

    /**
     * Handles the removal of an environment variable based on its ownership and delete mode.
     *
     * This method determines whether the variable is owned by the current environment
     * or inherited from a parent. It then performs the appropriate action:
     *
     * After the action is performed, a success toast is displayed and the component is reset.
     */
    public function removeVariable(): void
    {
        $this->authorizeEnvironment(variable: $this->variable, target: $this->targetEnvironment);

        if (! $this->isLocalToTarget) {
            // The variable is inherited from a parent environment and does not exist locally.
            // The only valid action is to suppress it in this environment to prevent inheritance.
            $this->suppressInherited();

        } elseif ($this->isOverride && $this->deleteMode === 'suppress') {
            // The variable is defined locally and overrides a parent value.
            // The user chose to suppress it instead of deleting it, which prevents fallback to the parent.
            $this->suppressOverride();

        } else {
            // The variable is owned by this environment and can be deleted directly.
            // This applies whether it’s a direct value or an override where the user chose to delete it.
            $this->deleteVariable();
        }

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->dispatch(self::DELETED, $this->environmentVariableId);

        $this->closeAndReset();
    }

    /**
     * Suppresses an inherited variable by creating a "tombstone" record.
     *
     * This method prevents the current environment from inheriting a variable
     * defined in a parent environment. It does not affect the original source
     * variable, but instead creates a local tombstone to block inheritance.
     */
    protected function suppressInherited(): void
    {
        resolve(SuppressInheritedVariable::class)->handle(
            key: $this->variable->key,
            environment: $this->targetEnvironment,
            suppressedBy: Auth::user()
        );

        $this->successToast(
            heading: 'Inherited Variable Supressed',
            text: sprintf('The key “%s” will no longer be inherited in this environment.', $this->variable->key)
        );
    }

    /**
     * Supress a locally defined override to block inheritance from a parent environment.
     *
     * This marks the current variable (owned by the target environment) as deleted,
     * effectively preventing the system from falling back to a value defined in any
     * parent environment. Unlike a full delete, this keeps the record as a "tombstone"
     * to explicitly block inheritance.
     */
    protected function suppressOverride(): void
    {
        resolve(SuppressOverrideVariable::class)->handle(
            var: $this->variable,
            suppressedBy: Auth::user()
        );

        $this->successToast(
            heading: 'Override Surpressed',
            text: sprintf('The key “%s” has been blocked from inheriting a value.', $this->variable->key)
        );
    }

    /**
     * Permanently deletes a variable from the current environment.
     *
     * This removes the variable from the database. If the variable is an override,
     * this action may cause the environment to fall back to an inherited value
     * from a parent environment (if one exists).
     */
    protected function deleteVariable(): void
    {
        app(DeleteVariable::class)->handle(
            var: $this->variable,
            deletedBy: Auth::user()
        );

        $this->successToast(
            heading: 'Variable Deleted',
            text: "The key “{$this->variable->key}” was deleted from this environment."
        );
    }

    /**
     * Reset the modal values.
     */
    protected function resetValues(): void
    {
        $this->reset('deleteMode');
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <div class="space-y-4">
                        <flux:heading size="lg">Remove Variable</flux:heading>

                        @if ($this->isLocalToTarget)
                            @if ($this->isOverride)
                                {{-- Variable is owned and shadows a parent — give both options --}}
                                <flux:radio.group wire:model.live="deleteMode" label="Choose what to do with this variable">
                                    <flux:radio value="delete" label="Delete (fall back to inherited value)" />
                                    <flux:radio value="suppress" label="Suppress in this environment" />
                                </flux:radio.group>

                                @switch($this->deleteMode)
                                    @case('delete')
                                        <flux:text class="mt-2">
                                            This will delete the overridden value and restore the inherited value of
                                            <flux:text class="inline" variant="strong">“{{ $this->variable?->key }}”</flux:text>
                                            from the parent environment.
                                        </flux:text>
                                        @break

                                    @case('suppress')
                                        <flux:text class="mt-2">
                                            This will suppress the
                                            <flux:text class="inline" variant="strong">“{{ $this->variable?->key }}”</flux:text>
                                            key in this environment, even if it exists in a parent environment.
                                        </flux:text>
                                        @break
                                @endswitch
                            @else
                                {{-- Standard delete, no override behavior --}}
                                <flux:text class="mt-2">
                                    This will permanently delete the variable
                                    <flux:text class="inline" variant="strong">“{{ $this->variable?->key }}”</flux:text>
                                    from this environment. There is no inherited value to fall back to.
                                </flux:text>
                            @endif
                        @else
                            {{-- Not owned — this is an inherited variable only --}}
                            <flux:text class="mt-2">
                                This variable is inherited from 
                                <flux:text class="inline" variant="strong">“{{ $this->variable?->environment->name }}”</flux:text>.
                                You can suppress it to prevent it from being used in this environment.
                            </flux:text>

                            <flux:text class="mt-2">
                                This will prevent the key 
                                <flux:text class="inline" variant="strong">“{{ $this->variable?->key }}”</flux:text>
                                from being inherited or used in this environment.
                            </flux:text>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>

                        <flux:button  
                            variant="danger"
                            wire:click="removeVariable">
                            {{ match($this->deleteMode) {
                                'delete' => 'Delete',
                                'suppress' => 'Suppress',
                                default => 'Confirm',
                            } }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
