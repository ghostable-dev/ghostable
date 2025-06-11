<?php

namespace App\Environment\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Actions\LogVariableRevealed;
use App\Environment\Actions\NormalizeEnvKey;
use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Actions\UpdateEnvVariable;
use App\Environment\Entities\UpdateEnvVariableData;
use App\Environment\Enums\CommonEnvKey;
use App\Environment\Models\EnvironmentVariable;
use App\Environment\Rules\EnvVariableRules;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class EnvironmentVariableEditor extends Component
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
     * The key of the environment variable being edited.
     */
    public string $key = '';

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
    public function launchEditorModal(EnvironmentVariable $variable): void
    {
        $this->authorize('perform', [$variable->environment, TeamPermission::EditVariables]);

        $this->environmentVariableId = $variable->id;
        $this->key = $variable->key;
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
     * Get a list of suggested environment variable keys
     * for the current environment.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function keySuggestions(): array
    {
        if (is_null($this->variable)) {
            return [];
        }

        return app(SuggestEnvKeys::class)->handle(
            $this->variable->environment
        );
    }

    /**
     * Get a list of suggested values for the currently selected environment variable key.
     *
     * Suggestions are provided based on the key, using the CommonEnvKey enum logic.
     * Returns an empty array if the key has no predefined suggestions.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function valueSuggestions(): array
    {
        return CommonEnvKey::suggestedValuesFor($this->key);
    }

    /**
     * Livewire lifecycle hook: triggered when the `key` property is updated.
     *
     * Normalizes the environment variable key by converting it to an uppercase
     * slug-style string using underscores (e.g., "app url" becomes "APP_URL").
     */
    public function updatedKey($value)
    {
        $this->key = app(NormalizeEnvKey::class)->handle($value);
    }

    /**
     * Update the selected environment variable with new key and/or value input.
     *
     * This method:
     * - Authorizes the user for the `EnvPush` permission on the variable's environment
     * - Skips the update entirely if no changes were made
     * - Validates the new key and value using update rules
     * - Applies the update and displays a success toast
     * - Closes the modal and resets component state
     */
    public function update(): void
    {
        $this->authorize('perform', [$this->variable->environment, TeamPermission::EditVariables]);

        // No actual changes were made.
        if ($this->noChangesWereMade()) {
            $this->showing = false;
            $this->reset('key', 'value', 'environmentVariableId');

            return;
        }

        $validated = $this->validate(EnvVariableRules::update());

        app(UpdateEnvVariable::class)->handle(
            $this->toUpdateVariableData($validated)
        );
        
        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

        Flux::toast(
            variant: 'success',
            heading: 'Variable Updated',
            text: "“{$this->key}” was successfully updated."
        );

        $this->dispatch(self::UPDATED, $this->environmentVariableId);
        $this->showing = false;
        $this->reset('key', 'value', 'environmentVariableId');
    }

    /**
     * Transform a raw input array into a UpdateEnvVariableData DTO.
     *
     * This helper is used to convert incoming data.
     * into a structured format suitable for updating an environment variable.
     * It automatically associates the current variable and authenticated user.
     */
    private function toUpdateVariableData(array $input): UpdateEnvVariableData
    {
        return new UpdateEnvVariableData(
            variable: $this->variable,
            value: $input['value'] ?? '',
            updatedBy: Auth::user()
        );
    }

    /**
     * Determine if the current key and value inputs match the original variable.
     *
     * This is used to detect whether any actual changes were made before saving.
     */
    #[Computed]
    public function noChangesWereMade(): bool
    {
        return $this->key === $this->variable?->key
            && $this->value === $this->variable?->value;
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Update Variable</flux:heading>
                    </div>
                    <div class="space-y-4">
                        <flux:input 
                            wire:model="key" 
                            readonly 
                            icon:trailing="lock-closed"
                            label="Key"/>
                        <flux:autocomplete 
                            wire:model.live="value" 
                            label="Value" 
                            required>
                            @foreach($this->valueSuggestions as $suggestion)
                                <flux:autocomplete.item>
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
                                wire:click="update">
                                {{ __('Update') }}
                            </flux:button>
                        @else
                            <x-auth.confirms-password wire:then="update">
                                <flux:button  
                                    variant="primary"
                                    :loading="true"
                                    wire:target="update">
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
