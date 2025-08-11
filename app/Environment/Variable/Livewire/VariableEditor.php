<?php

namespace App\Environment\Variable\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Actions\GetSuggestedVariableValues;
use App\Environment\Variable\Actions\LogVariableRevealed;
use App\Environment\Variable\Actions\UpdateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Entities\UpdateVariableData;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Variable\Rules\VariableRules;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class VariableEditor extends VariableModalComponent
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
     * The value of the environment variable being edited.
     */
    public string $value = '';

    /**
     * Livewire event listener to launch the environment variable modal.
     */
    #[On(self::LAUNCH)]
    public function launchModal(EnvironmentVariable $variable, ?Environment $targetEnvironment = null): void
    {
        $this->authorizeEnvironment(variable: $variable, target: $targetEnvironment);

        $this->value = $variable->value;

        if ($variable->isSecret()) {
            app(LogVariableRevealed::class)->handle($variable);
        }

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

        parent::launchModal($variable, $targetEnvironment);
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
        $this->authorizeEnvironment(variable: $this->variable, target: $this->targetEnvironment);

        if ($this->noChangesWereMade()) {
            $this->closeAndReset();

            return;
        }

        $validated = $this->validate(VariableRules::update());

        $this->isLocalToTarget
            ? $this->update($validated)
            : $this->createOverride($validated);

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->dispatch(self::UPDATED, $this->environmentVariableId);
        $this->closeAndReset();
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
            new UpdateVariableData(
                variable: $this->variable,
                value: $input['value'] ?? '',
                updatedBy: Auth::user()
            )
        );

        $this->successToast('Variable Updated', "“{$this->variable->key}” was successfully updated.");
    }

    /**
     * Create environment variable.
     */
    private function createOverride(array $input): void
    {
        resolve(CreateVariable::class)->handle(
            new CreateVariableData(
                environment: $this->targetEnvironment,
                key: $this->variable->key,
                value: $input['value'],
                is_override: true,
                createdBy: Auth::user()
            )
        );

        $this->successToast(
            'Override Created',
            "“{$this->variable->key}” now overrides the inherited value in this environment."
        );
    }

    /**
     * Reset the modal values.
     */
    protected function resetValues(): void
    {
        $this->reset('value', 'environmentVariableId', 'targetEnvironmentId');
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">
                            {{ $this->isLocalToTarget ? 'Update' : 'Override' }} Variable
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
