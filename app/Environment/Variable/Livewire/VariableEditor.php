<?php

namespace App\Environment\Variable\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Actions\GetSuggestedVariableValues;
use App\Environment\Variable\Actions\LogVariableRevealed;
use App\Environment\Variable\Actions\UpdateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Entities\UpdateVariableData;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Variable\Rules\VariableRules;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class VariableEditor extends Component
{
    use ConfirmsPasswords;

    /**
     * Event name used to trigger the variable editor modal.
     */
    public const LAUNCH = 'variable-editor:launch';

    /**
     * Event name used to notify listeners the variable was updated.
     */
    public const UPDATED = 'variable-editor:updated';

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
     * The value of the environment variable being edited.
     */
    public string $value = '';

    /**
     * Livewire event listener to launch the environment variable editor modal.
     *
     * This method:
     * - Authorizes the user to push to the variable’s environment
     * - Loads the selected variable’s data into the component state
     * - Triggers the UI to show the modal
     */
    #[On(self::LAUNCH)]
    public function launchEditorModal(
        EnvironmentVariable $variable,
        ?Environment $targetEnvironment = null
    ): void {
        $this->authorize('perform', [$variable->environment, TeamPermission::EditVariables]);

        $this->environmentVariableId = $variable->id;
        $this->targetEnvironmentId = $targetEnvironment?->id;
        $this->value = $variable->value;

        if ($this->variable()->isSecret()) {
            app(LogVariableRevealed::class)->handle($this->variable);
        }

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

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
     * Retrieve the target environment instance based
     * on optionally provided environment ID.
     */
    #[Computed]
    public function targetEnvironment(): ?Environment
    {
        return $this->targetEnvironmentId
            ? Environment::find($this->targetEnvironmentId)
            : $this->variable?->environment ?? null;
    }

    /**
     * Get a list of suggested values for the currently selected environment variable key.
     *
     * Suggestions are provided by the VariableRegistry based on the key's
     * corresponding definition class. Returns an empty array if the key has no suggestions defined.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function valueSuggestions(): array
    {
        if (! $this->variable) {
            return [];
        }

        return app(GetSuggestedVariableValues::class)->handle($this->variable?->key);
    }

    /**
     * Update the selected environment variable with new key and/or value input.
     */
    public function updateVariable(): void
    {
        $this->authorizeEditOrOverride();

        if ($this->noChangesWereMade()) {
            $this->closeAndReset();

            return;
        }

        $validated = $this->validate(VariableRules::update());

        $this->isEditingDirectVariable
            ? $this->update($validated)
            : $this->createOverride($validated);

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->dispatch(self::UPDATED, $this->environmentVariableId);
        $this->closeAndReset();
    }

    /**
     * Authorize the edit or override operation.
     */
    private function authorizeEditOrOverride(): void
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
     * Editing a variable direct inside the target variable.
     */
    #[Computed]
    public function isEditingDirectVariable(): bool
    {
        return $this->variable?->belongsToEnvironment($this->targetEnvironment) ?? true;
    }

    /**
     * Determine if the current value input matches the original variable.
     *
     * This is used to detect whether any actual changes were made before saving.
     */
    #[Computed]
    public function noChangesWereMade(): bool
    {
        return $this->value === $this->variable?->value;
    }

    /**
     * Updated environment variable.
     */
    private function update(array $input): void
    {
        resolve(UpdateVariable::class)->handle(
            $this->toUpdateVariableData($input)
        );

        $this->successToast(
            'Variable Updated',
            "“{$this->variable->key}” was successfully updated."
        );
    }

    /**
     * Transform a raw input array into a UpdateVariableData DTO.
     */
    private function toUpdateVariableData(array $input): UpdateVariableData
    {
        return new UpdateVariableData(
            variable: $this->variable,
            value: $input['value'] ?? '',
            updatedBy: Auth::user()
        );
    }

    /**
     * Create environment variable.
     */
    private function createOverride(array $input): void
    {
        resolve(CreateVariable::class)->handle(
            $this->toCreateVariableData($input)
        );

        $this->successToast(
            'Override Created',
            "“{$this->variable->key}” now overrides the inherited value in this environment."
        );
    }

    /**
     * Transform a raw input array into a CreateVariableData DTO.
     */
    private function toCreateVariableData(array $input): CreateVariableData
    {
        return new CreateVariableData(
            environment: $this->targetEnvironment,
            key: $this->variable->key,
            value: $input['value'],
            is_override: true,
            createdBy: Auth::user()
        );
    }

    /**
     * Display a "success" toast.
     */
    private function successToast(string $heading, string $text): void
    {
        Flux::toast(variant: 'success', heading: $heading, text: $text);
    }

    /**
     * Reset the editor state.
     */
    private function closeAndReset(): void
    {
        $this->showing = false;

        $this->reset('value', 'environmentVariableId', 'targetEnvironmentId');
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">
                            {{ $this->isEditingDirectVariable ? 'Update' : 'Override' }} Variable
                        </flux:heading>
                    </div>
                    <div class="space-y-4">
                        <flux:input 
                            value="{{ $this->variable?->key }}" 
                            readonly 
                            icon:trailing="lock-closed"
                            label="Key"/>
                        <flux:autocomplete 
                            wire:model.live="value"
                            label="Value"
                            wire:keydown.enter="$dispatch('confirm-password', { then: 'update' })"
                            autofocus
                            required>
                            @foreach($this->valueSuggestions as $suggestion)
                                <flux:autocomplete.item wire:key="value-{{ $suggestion }}">
                                    {{ $suggestion }}
                                </flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        @if($this->noChangesWereMade)
                            <flux:button  
                                variant="primary"
                                wire:click="updateVariable">
                                {{ __('Update') }}
                            </flux:button>
                        @else
                            <x-auth.confirms-password wire:then="updateVariable">
                                <flux:button  
                                    variant="primary"
                                    :loading="true"
                                    wire:target="updateVariable">
                                    {{ __('Update') }}
                                </flux:button>
                            </x-auth.confirms-password>
                        @endif
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
