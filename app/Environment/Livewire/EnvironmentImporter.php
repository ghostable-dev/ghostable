<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\ImportEnvironment;
use App\Environment\Enums\PushMode;
use App\Organization\Enums\OrganizationPermission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class EnvironmentImporter extends EnvironmentComponent
{
    /**
     * Events
     */
    public const LAUNCH = 'environment-importer:launch';

    public const IMPORTED = 'environment-importer:imported';

    /**
     * Indicates whether the modal is currently visible.
     */
    public bool $showing = false;

    /**
     * The variables input.
     */
    public string $input = '';

    /**
     * Livewire event listener to launch the environment modal.
     */
    #[On(self::LAUNCH)]
    public function launchModal(): void
    {
        $this->showing = true;
    }

    #[Computed(persist: true)]
    public function description(): string
    {
        return PushMode::ADDITIVE->description();
    }

    /**
     * Import environment variables from pasted input.
     */
    public function import(): void
    {
        $this->authorize('perform', [$this->environment, OrganizationPermission::EditVariables]);

        if (blank($this->input)) {
            return;
        }

        resolve(ImportEnvironment::class)->handle(
            environment: $this->environment,
            rawInput: $this->input,
            importedBy: Auth::user()
        );

        $this->reset('input');
        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->dispatch(self::IMPORTED);
        $this->showing = false;
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <div class="space-y-4">
                        <flux:heading size="lg">Import Environment File</flux:heading>
                        <flux:callout variant="secondary" icon="information-circle" heading="{{ $this->description }}" />
                        <flux:textarea wire:model.defer="input" rows="12" label="Copy and paste the environment file contents." />
                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" wire:click="import">
                                Import
                            </flux:button>
                        </div>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
