<?php

namespace App\Secret\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Secret\Actions\CreateSecret;
use App\Secret\Actions\DeleteSecret;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class SecretsManager extends Component
{
    use ConfirmsPasswords;

    #[Locked]
    public string $ownerType;

    #[Locked]
    public string $ownerId;

    public string $name = '';

    public SecretType $type = SecretType::GENERIC;

    public string $value = '';

    public bool $showCreateModal = false;

    public ?string $viewingSecretId = null;

    public ?string $secretToRemoveId = null;

    #[On(SecretEditor::UPDATED)]
    public function refreshSecrets(): void
    {
        $this->owner->refresh();
    }

    #[Computed(persist: true)]
    public function canEditSecrets(): bool
    {
        return Gate::allows('perform', [$this->owner, TeamPermission::EditSecrets]);
    }

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

    public function editSecret(Secret $secret): void
    {
        $this->dispatch(SecretEditor::LAUNCH, $secret->id);
    }

    public function confirmSecretRemoval(Secret $secret): void
    {
        $this->secretToRemoveId = $secret->id;

        $this->authorize('perform', [$secret->owner, TeamPermission::EditSecrets]);

        Flux::modal('confirm-secret-removal')->show();
    }

    #[Computed]
    public function secretToRemove(): ?Secret
    {
        return Secret::find($this->secretToRemoveId);
    }

    public function removeSecret(): void
    {
        $secret = $this->secretToRemove;
        $this->authorize('perform', [$secret->owner, TeamPermission::EditSecrets]);

        app(DeleteSecret::class)->handle(
            secret: $secret,
            deletedBy: Auth::user(),
        );

        Flux::modal('confirm-secret-removal')->close();
        Flux::toast("Secret '{$secret->name}' removed.");

        $this->owner->refresh();
        $this->reset('secretToRemoveId');
    }

    public function render()
    {
        return view('secret.secrets-manager');
    }
}
