<?php

namespace App\Secret\Livewire;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Organization\Enums\OrganizationPermission;
use App\Secret\Actions\CreateSecret;
use App\Secret\Actions\DeleteSecret;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class SecretsManager extends Component
{
    #[Locked]
    public string $environmentId;

    public string $name = '';

    public SecretType $type = SecretType::GENERIC;

    public string $value = '';

    public bool $showCreateModal = false;

    public ?string $viewingSecretId = null;

    public ?string $secretToRemoveId = null;

    public function mount(Environment $environment): void
    {
        $this->authorize('view', $environment);

        $this->environmentId = $environment->id;
    }

    #[Computed]
    public function environment(): Environment
    {
        return ResolveEnvironment::onceWithContext($this->environmentId);
    }

    #[On(SecretEditor::UPDATED)]
    public function refreshSecrets(): void
    {
        $this->environment->refresh();
    }

    #[Computed(persist: true)]
    public function canEditSecrets(): bool
    {
        return Gate::allows('perform', [$this->environment, OrganizationPermission::EditSecrets]);
    }

    #[Computed]
    public function secrets()
    {
        return $this->environment->secrets()->with('latestVersion')->get();
    }

    public function createSecret(): void
    {
        $this->validate([
            'name' => 'required|max:255',
            'value' => 'required',
            'type' => 'required',
        ]);

        $secret = app(CreateSecret::class)->handle(
            environment: $this->environment,
            name: $this->name,
            type: $this->type,
            value: $this->value,
            metadata: null,
            createdBy: Auth::user(),
        );

        Flux::toast("Secret '{$secret->name}' added.");

        $this->reset('name', 'value');
        $this->showCreateModal = false;

        $this->environment->refresh();
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

        $this->authorize('perform', [$secret->environment, OrganizationPermission::EditSecrets]);

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
        $this->authorize('perform', [$secret->environment, OrganizationPermission::EditSecrets]);

        app(DeleteSecret::class)->handle(
            secret: $secret,
            deletedBy: Auth::user(),
        );

        Flux::modal('confirm-secret-removal')->close();
        Flux::toast("Secret '{$secret->name}' removed.");

        $this->environment->refresh();
        $this->reset('secretToRemoveId');
    }

    public function render()
    {
        return view('environment.environment-secrets-manager');
    }
}
