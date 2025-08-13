<?php

namespace App\Environment\Variable\Livewire;

use App\Environment\Actions\ImportEnvironmentVariables;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Team\Enums\TeamPermission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class VariableImporter extends Component
{
    /**
     * Events
     */
    public const LAUNCH = 'variable-importer:launch';
    public const IMPORTED = 'variable-importer:imported';

    /**
     * Indicates whether the variable editor modal is currently visible.
     */
    public bool $showing = false;

    /**
     * The ID of the environment currently being managed.
     */
    #[Locked]
    public string $environmentId;
    
    /**
     * The variables input.
     */
    public string $input = '';
    
    public function mount(Environment $environment): void
    {
        $this->environmentId = $environment->id;
    }
    
    /**
     * Livewire event listener to launch the environment modal.
     */
    #[On(self::LAUNCH)]
    public function launchModal(): void 
    {
        $this->showing = true;
    }

    /**
     * Retrieve the current environment instance based on the provided environment ID.
     *
     * This method will throw a ModelNotFoundException if the environment does not exist.
     */
    #[Computed]
    public function environment(): Environment
    {
        return ResolveEnvironment::onceWithContext($this->environmentId);
    }
    
    /**
     * Import environment variables from pasted input.
    */
    public function import(): void
    {
        $this->authorize('perform', [$this->environment, TeamPermission::EditVariables]);

        if (blank($this->input)) {
            return;
        }

        resolve(ImportEnvironmentVariables::class)->handle(
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
