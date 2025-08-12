<div class="space-y-6">
    <x-section>
        <x-slot:title>Secrets</x-slot:title>
        <x-slot:subheading>
            <div class="max-w-2xl">
                Secrets securely store credentials like API tokens.
                Manage and rotate them from this section.
            </div>
        </x-slot:subheading>
        <x-slot:actions>
            <flux:button variant="primary" wire:click="$set('showCreateModal', true)" icon:trailing="plus">
                New Secret
            </flux:button>
        </x-slot:actions>

        @if(count($this->secrets))
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Version</flux:table.column>
                    <flux:table.column>Age</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->secrets as $secret)
                        <flux:table.row wire:key="secret-{{ $secret->id }}">
                            <flux:table.cell>
                                <flux:text size="sm">{{ $secret->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $secret->latestVersion->version }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $secret->last_updated_at->shortAbsoluteDiffForHumans() }}
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:dropdown position="left">
                                    <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item wire:click="confirmShowSecret('{{ $secret->id }}')">
                                            View
                                        </flux:menu.item>
                                        @if($this->canEditSecrets)
                                            <flux:menu.item wire:click="editSecret('{{ $secret->id }}')">
                                                Edit
                                            </flux:menu.item>
                                            <flux:menu.item wire:click="confirmSecretRemoval('{{ $secret->id }}')" variant="danger">
                                                Delete
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:callout.heading>No secrets</flux:callout.heading>
            <flux:callout.text>You haven't created any secrets yet.</flux:callout.text>
        @endif
    </x-section>

    <flux:modal wire:model="showCreateModal" class="md:w-lg">
        <form wire:submit="createSecret" class="space-y-6">
            <div>
                <flux:heading size="lg">Create Secret</flux:heading>
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
                <flux:button type="submit" variant="primary">Create</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="viewingSecretId" class="md:w-lg">
        @if($this->viewingSecret)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $this->viewingSecret->name }}
                    </flux:heading>
                </div>
                <flux:input value="{{ $this->viewingSecret->value }}" copyable readonly />
                <div class="flex gap-2 justify-end">
                    <flux:button variant="filled" wire:click="closeViewModal">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <livewire:secret.livewire.secret-editor />

    <flux:modal name="confirm-secret-removal" class="md:w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove Secret</flux:heading>
                <flux:text class="mt-2">
                    Are you sure you want to remove the
                    <flux:text class="inline" variant="strong">
                        “{{ $this->secretToRemove?->name }}”
                    </flux:text>
                    secret?
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="removeSecret">
                    {{ __('Remove Secret') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
