<?php

namespace App\Environment\Livewire;

use App\Environment\Enums\CommonEnvKey;
use App\Environment\Models\Environment;
use App\Environment\Rules\EnvVariableRules;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EnvironmentVarCreateModal extends Component
{
    #[Locked]
    public string $envId;

    public string $key = '';
    public string $value = '';

    public function mount(Environment $environment): void
    {
        $this->authorize('update', $environment);
        
        $this->envId = $environment->id;
    }
    
    public function rules(): array
    {
        return EnvVariableRules::create($this->environment);
    }

    #[Computed()]
    public function environment(): Environment
    {
        return Environment::findOrFail($this->envId);
    }
    
    #[Computed()]
    public function keySuggestions(): array
    {
        $currentEnv = $this->environment;

        // Get keys from other environments in the same project
        $otherEnvs = $currentEnv->project->environments()
            ->where('id', '!=', $currentEnv->id)
            ->with('variables')
            ->get();

        $otherKeys = $otherEnvs
            ->flatMap(fn ($env) => $env->variables->pluck('key'))
            ->unique()
            ->values()
            ->all();

        // Standard Laravel keys from the enum
        $standardKeys = CommonEnvKey::values();

        // Keys already used in this environment
        $existingKeys = $currentEnv->variables->pluck('key')->all();

        // Merge standard + project keys, exclude current env keys
        return collect($standardKeys)
            ->merge($otherKeys)
            ->unique()
            ->reject(fn ($key) => in_array($key, $existingKeys))
            ->values()
            ->all();
    }

    public function create()
    {
        $this->authorize('update', $this->environment);
        
        $this->validate();

        $this->environment->variables()->create([
            'key' => $this->key,
            'value' => $this->value,
        ]);

        $this->reset('key', 'value');

        Flux::modal('create-env-var')->close();
        Flux::toast('New environment variable created.');
        
        $this->dispatch('env-variable-created');
    }
    
    public function updatedKey($value)
    {
        $this->key = str($value)->slug('_')->upper();
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal name="create-env-var" class="md:w-96">
                <form wire:submit="create" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Add Variable</flux:heading>
                        <flux:text class="mt-2">Define a new key-value pair in this environment.</flux:text>
                    </div>
                    <flux:autocomplete wire:model="key" label="Key" required>
                        @foreach($this->keySuggestions as $suggestion)
                            <flux:autocomplete.item>
                                {{ $suggestion }}
                            </flux:autocomplete.item>
                        @endforeach
                    </flux:autocomplete>
                    <flux:input
                        label="Value"
                        wire:model="value"
                        required
                    />
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Add Variable</flux:button>
                    </div>
                </form>
            </flux:modal>
        BLADE;
    }
}