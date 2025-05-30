<?php

namespace App\Environment\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Models\Environment;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class EnvironmentView extends Component
{
    use ConfirmsPasswords;

    #[Locked]
    public string $envId;

    public array $showing = [];

    public $editing = null;

    public $editedValues = [];

    public function mount(Environment $environment): void
    {
        $this->authorize('view', $environment);

        $this->forcePasswordConfirmation();

        $this->envId = $environment->id;
    }

    public function edit($id)
    {
        $this->authorize('update', $this->environment);

        $variable = $this->environment->variables->firstWhere('id', $id);
        $this->editing = $id;
        $this->editedValues[$id] = $variable->value;
    }

    public function save($id)
    {
        $this->authorize('update', $this->environment);

        $variable = $this->environment->variables->firstWhere('id', $id);
        $variable->update(['value' => $this->editedValues[$id]]);
        $this->editing = null;

        $this->environment->refresh();
    }

    public function cancelEdit()
    {
        $this->editing = null;
    }

    public function delete(string $id)
    {
        $this->authorize('update', $this->environment);

        $variable = $this->environment->variables->firstWhere('id', $id);

        if (! $variable) {
            return;
        }

        $variable->delete();

        $this->environment->refresh();

        Flux::toast("Variable '{$variable->key}' deleted.");
    }

    #[Computed()]
    public function environment(): Environment
    {
        return Environment::findOrFail($this->envId);
    }

    public function toggleShow(string $id): void
    {
        // Flip visibility for the given ID
        $this->showing[$id] = ! ($this->showing[$id] ?? false);
    }

    #[On('env-variable-created')]
    public function refresh(): void
    {
        $this->environment->refresh();
    }

    public function render()
    {
        return view('environment.environment-view');
    }
}
