<?php

namespace App\Secret\Livewire;

use App\Organization\Enums\OrganizationPermission;
use App\Secret\Actions\UpdateSecret;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SecretEditor extends Component
{
    public const LAUNCH = 'secret-editor:launch';

    public const UPDATED = 'secret-editor:updated';

    public bool $showing = false;

    public ?string $secretId = null;

    public string $name = '';

    public SecretType $type = SecretType::GENERIC;

    public string $value = '';

    #[On(self::LAUNCH)]
    public function launchEditorModal(Secret $secret): void
    {
        $this->authorize('perform', [$secret->environment, OrganizationPermission::EditSecrets]);

        $this->secretId = $secret->id;
        $this->name = $secret->name;
        $this->type = $secret->type;
        $this->value = $secret->value;

        $this->showing = true;
    }

    #[Computed]
    public function secret(): ?Secret
    {
        return Secret::find($this->secretId);
    }

    #[Computed]
    public function noChangesWereMade(): bool
    {
        return $this->secret &&
            $this->name === $this->secret->name &&
            $this->type->value === $this->secret->type->value &&
            $this->value === $this->secret->value;
    }

    public function updateSecret(): void
    {
        $this->authorize('perform', [$this->secret->environment, OrganizationPermission::EditSecrets]);

        if ($this->noChangesWereMade) {
            $this->showing = false;
            $this->reset('secretId', 'name', 'type', 'value');

            return;
        }

        $this->validate([
            'name' => 'required|max:255',
            'value' => 'required',
            'type' => 'required',
        ]);

        app(UpdateSecret::class)->handle(
            secret: $this->secret,
            name: $this->name,
            type: $this->type,
            value: $this->value,
            metadata: null,
            updatedBy: Auth::user(),
        );

        Flux::toast(
            variant: 'success',
            heading: 'Secret Updated',
            text: "“{$this->name}” was successfully updated."
        );

        $this->dispatch(self::UPDATED, $this->secretId);

        $this->showing = false;
        $this->reset('secretId', 'name', 'type', 'value');
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Update Secret</flux:heading>
                    </div>
                    <flux:input label="Name" wire:model="name" required />
                    <flux:select label="Type" wire:model="type">
                        @foreach(\App\Secret\Enums\SecretType::cases() as $option)
                            <flux:select.option wire:key="type-{{ $option->value }}" value="{{ $option->value }}">
                                {{ $option->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:textarea label="Value" wire:model="value" required />
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button variant="primary" wire:click="updateSecret">
                            Update
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
