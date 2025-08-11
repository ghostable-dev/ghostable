<?php

namespace App\Environment\Variable\Livewire;

use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\ReinstateInheritedVariable;
use App\Environment\Variable\Actions\ReinstateOverrideVariable;
use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class VariableReinstater extends VariableModalComponent
{
    /**
     * Events
     */
    public const LAUNCH = 'variable-reinstater:launch';

    public const REINSTATED = 'variable-reinstated:reinstated';

    /**
     * Modes
     */
    private const MODE_USE_INHERITED = 'use_inherited';

    private const MODE_USE_OVERRIDE = 'use_override';

    /**
     * Reinstate mode.
     */
    public string $mode = self::MODE_USE_INHERITED;

    /**
     * Listener to open the modal.
     */
    #[On(self::LAUNCH)]
    public function launchModal(EnvironmentVariable $variable, ?Environment $targetEnvironment = null): void
    {
        $this->authorizeEnvironment(variable: $variable, target: $targetEnvironment);

        $this->mode = $variable->is_override
            ? self::MODE_USE_OVERRIDE
            : self::MODE_USE_INHERITED;

        parent::launchModal($variable, $targetEnvironment);
    }

    /**
     * Perform the restore according to the chosen mode.
     */
    public function reinstateVariable(): void
    {
        $this->authorizeEnvironment(variable: $this->variable, target: $this->targetEnvironment);

        if ($this->variable->is_override) {
            if ($this->mode === self::MODE_USE_OVERRIDE) {
                resolve(ReinstateOverrideVariable::class)->handle(
                    var: $this->variable,
                    reinstatedBy: Auth::user(),
                );
            } else {
                resolve(ReinstateInheritedVariable::class)->handle(
                    var: $this->variable,
                    reinstatedBy: Auth::user(),
                );
            }
        } else {
            resolve(ReinstateInheritedVariable::class)->handle(
                var: $this->variable,
                reinstatedBy: Auth::user(),
            );
        }

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->dispatch(self::REINSTATED, $this->environmentVariableId);

        $this->closeAndReset();
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <div class="space-y-4">
                        <flux:heading size="lg">Restore Variable</flux:heading>

                        @if ($this->isOverride)
                            {{-- The variable is overridden in this environment.
                                 Let the user choose what "restore" means. --}}
                            <flux:radio.group wire:model.live="mode" label="Choose how to restore “{{ $this->variable?->key }}”">
                                <flux:radio value="{{ self::MODE_USE_OVERRIDE }}"  label="Restore override value in this environment" />
                                <flux:radio value="{{ self::MODE_USE_INHERITED }}" label="Use inherited value from parent environment" />
                            </flux:radio.group>

                            @switch($this->mode)
                                @case(self::MODE_USE_OVERRIDE)
                                    <flux:text class="mt-2">
                                        This will keep (or re-enable) the override for
                                        <flux:text class="inline" variant="strong">“{{ $this->variable?->key }}”</flux:text>
                                        in this environment. If a block/tombstone exists, it will be cleared so the override applies.
                                    </flux:text>
                                    @break

                                @case(self::MODE_USE_INHERITED)
                                    <flux:text class="mt-2">
                                        This will remove the override for
                                        <flux:text class="inline" variant="strong">“{{ $this->variable?->key }}”</flux:text>
                                        so the value is inherited from the parent environment.
                                    </flux:text>
                                    @break
                            @endswitch
                        @else
                            {{-- Not an override: it's tombstoned (blocked) here. --}}
                            <flux:text class="mt-2">
                                This will remove the block on
                                <flux:text class="inline" variant="strong">“{{ $this->variable?->key }}”</flux:text>
                                and restore inheritance from the parent environment.
                            </flux:text>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        
                        <flux:button
                            variant="primary"
                            wire:click="reinstateVariable">
                            {{ $this->isOverride
                                ? ($this->mode === self::MODE_USE_OVERRIDE ? 'Reinstate Override' : 'Use Inherited')
                                : 'Reinstate'
                            }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
