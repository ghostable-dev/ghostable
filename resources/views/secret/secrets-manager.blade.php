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

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Version</flux:table.column>
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
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="eye" wire:click="confirmShowSecret('{{ $secret->id }}')">
                                View
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
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
                    <flux:heading size="lg">{{ $this->viewingSecret->name }}</flux:heading>
                </div>
                <flux:input value="{{ $this->viewingSecret->value }}" copyable readonly />
                <div class="flex gap-2 justify-end">
                    <flux:button variant="filled" wire:click="closeViewModal">Close</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
