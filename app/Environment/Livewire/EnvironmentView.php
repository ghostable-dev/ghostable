<?php

namespace App\Environment\Livewire;

use App\Environment\Models\Environment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EnvironmentView extends Component
{
    #[Locked]
    public string $envId;

    public array $showing = [];

    public $editing = null;

    public $editedValues = [];

    public function edit($id)
    {
        $variable = $this->environment->variables->firstWhere('id', $id);
        $this->editing = $id;
        $this->editedValues[$id] = $variable->value;
    }

    public function save($id)
    {
        $variable = $this->environment->variables->firstWhere('id', $id);
        $variable->update(['value' => $this->editedValues[$id]]);
        $this->editing = null;
    }

    public function cancelEdit()
    {
        $this->editing = null;
    }

    public function mount(Environment $environment): void
    {
        $this->envId = $environment->id;
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

    public function render()
    {
        return view('environment.environment-view');
    }
}
