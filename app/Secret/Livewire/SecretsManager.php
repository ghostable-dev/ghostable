<?php

namespace App\Secret\Livewire;

use App\Secret\Actions\CreateSecret;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Flux\Flux;

class SecretsManager extends Component
{
    #[Locked]
    public string $ownerType;

    #[Locked]
    public string $ownerId;

    public string $name = '';
    public SecretType $type = SecretType::GENERIC;
    public string $value = '';

    public bool $showCreateModal = false;
    public ?string $viewingSecretId = null;

    public function mount(Model $owner): void
    {
        $this->ownerType = $owner->getMorphClass();
        $this->ownerId = $owner->getKey();
    }

    #[Computed]
    public function owner(): Model
    {
        $class = Relation::getMorphedModel($this->ownerType) ?? $this->ownerType;

        return $class::findOrFail($this->ownerId);
    }

    #[Computed]
    public function secrets()
    {
        return $this->owner->secrets()->with('latestVersion')->get();
    }

    public function createSecret(): void
    {
        $this->validate([
            'name' => 'required|max:255',
            'value' => 'required',
            'type' => 'required',
        ]);

        $secret = app(CreateSecret::class)->handle(
            owner: $this->owner,
            name: $this->name,
            type: $this->type,
            value: $this->value,
            metadata: null,
            createdBy: Auth::user(),
        );

        Flux::toast("Secret '{$secret->name}' added.");

        $this->reset('name', 'value');
        $this->showCreateModal = false;
    }

    public function confirmShowSecret(Secret $secret): void
    {
        $this->viewingSecretId = $secret->id;
    }

    #[Computed]
    public function viewingSecret(): ?Secret
    {
        return Secret::find($this->viewingSecretId);
    }

    public function closeViewModal(): void
    {
        $this->viewingSecretId = null;
    }

    public function render()
    {
        return view('secret.secrets-manager');
    }
}
