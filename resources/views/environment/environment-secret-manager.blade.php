<x-layouts.environment :environment="$this->environment">
    
    <div class="space-y-6">
    <x-section>
        <x-slot:title>Variables (Zero-Knowledge)</x-slot:title>
        <x-slot:subheading>
            <div class="max-w-xl">
                Only ciphertext and metadata are ever stored on Ghostable. This ensures your sensitive values can’t be read, even by us.
            </div>
        </x-slot:subheading>

        @if(count($this->variables))
            <flux:table>
                <flux:table.columns>
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'name'" 
                        :direction="$sortDirection" 
                        wire:click="sort('name')">Name</flux:table.column>
                    <flux:table.column>Size</flux:table.column>
                    <flux:table.column>Version</flux:table.column>
                    <flux:table.column
                    sortable 
                    :sorted="$sortBy === 'last_updated_at'" 
                    :direction="$sortDirection" 
                    wire:click="sort('last_updated_at')">Last Updated</flux:table.column>
                    <flux:table.column>By</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->variables as $secret)
                        <flux:table.row
                            wire:key="secret-{{ $secret->id }}"
                            @class(['opacity-50' => $secret->is_commented])
                        >
                            <flux:table.cell variant="strong">
                                <div class="flex items-center gap-2">
                                    {{ $secret->name }}
                                    @if($secret->is_commented)
                                        <flux:badge size="sm" color="slate" icon="hashtag">
                                            Commented
                                        </flux:badge>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $secret->displayLineBytes }}
                            </flux:table.cell>
                            <flux:table.cell>
                                v{{ $secret->version }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $secret->last_updated_at->timezone(timezone())->format(DT_FORMAT)  }}
                            </flux:table.cell>
                            <flux:table.cell variant="strong">
                                {{ $secret->lastUpdatedBy->email }}
                            </flux:table.cell>
                            <flux:table.cell align="end" class="flex items-center gap-2 justify-end">
                                <flux:dropdown position="left">
                                    <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item wire:click="viewDetails('{{ $secret->id }}')">
                                            Details
                                        </flux:menu.item>
                                        {{-- <flux:menu.item wire:click="viewActivity('{{ $secret->id }}')">
                                            Activity
                                        </flux:menu.item> --}}
                                        @if($secret->latestVersion->version > 1)
                                            <flux:menu.item wire:click="viewVersions('{{ $secret->id }}')">
                                                Change Log
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
    
    <livewire:environment.livewire.environment-secret-activity-feed />
    
    <livewire:environment.livewire.environment-secret-details-viewer />
    
    <livewire:environment.livewire.environment-secret-version-manager />
  
</div>
    
</x-layouts.environment>
