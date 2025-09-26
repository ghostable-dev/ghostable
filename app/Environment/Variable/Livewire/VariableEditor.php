<?php

namespace App\Environment\Variable\Livewire;

use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Actions\GetSuggestedVariableValues;
use App\Environment\Variable\Actions\LogVariableRevealed;
use App\Environment\Variable\Actions\UpdateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Entities\UpdateVariableData;
use App\Environment\Variable\Enums\DeliveryMode;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Variable\Rules\VariableRules;
use App\Project\Enums\DeploymentProvider;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class VariableEditor extends VariableModalComponent
{
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
     * The "delivery_mode" of the variable (only applies to Vapor deployment types.)
     */
    public DeliveryMode $delivery_mode;

    /**
     * Livewire event listener to launch the environment variable modal.
     */
    #[On(self::LAUNCH)]
    public function launchModal(EnvironmentVariable $variable, ?Environment $targetEnvironment = null): void
    {
        $this->authorizeEnvironment(variable: $variable, target: $targetEnvironment);

        $this->value = $variable->value;

        $this->delivery_mode = $variable->delivery_mode;

        if ($variable->isSecret()) {
            app(LogVariableRevealed::class)->handle($variable);
        }

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

        parent::launchModal($variable, $targetEnvironment);
    }

    #[Computed(persist: false)]
    public function isVaporProject(): bool
    {
        return $this->targetEnvironment()?->project->deployment_provider === DeploymentProvider::LARAVEL_VAPOR;
    }

    #[Computed(persist: true)]
    public function deliveryModes(): array
    {
        return DeliveryMode::cases();
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
        return $this->value === $this->variable?->value
            && $this->delivery_mode === $this->variable?->delivery_mode;
    }

    /**
     * Updated environment variable.
     */
    private function update(array $input): void
    {
        resolve(UpdateVariable::class)->handle(
            new UpdateVariableData(
                variable: $this->variable,
                delivery_mode: $input['delivery_mode'],
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
                delivery_mode: $input['delivery_mode'],
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
        $this->reset('value', 'delivery_mode', 'environmentVariableId', 'targetEnvironmentId');
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
                            wire:keydown.enter="updateVariable"
                            autofocus
                            required>
                            @foreach($this->valueSuggestions as $suggestion)
                                <flux:autocomplete.item wire:key="value-{{ $suggestion }}">
                                    {{ $suggestion }}
                                </flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                        @if($this->isVaporProject)
                            <flux:radio.group 
                                label="Delivery Mode" 
                                wire:model.live="delivery_mode"
                                variant="cards"
                                class="flex-col">
                                @foreach($this->deliveryModes as $mode)
                                    <flux:radio
                                        name="delivery_mode"
                                        value="{{ $mode->value }}"
                                        label="{{ $mode->label() }}"
                                        description="{{ $mode->description() }}"
                                    />
                                @endforeach
                            </flux:radio.group>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button  
                            variant="primary"
                            wire:click="updateVariable">
                            {{ __('Update') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
