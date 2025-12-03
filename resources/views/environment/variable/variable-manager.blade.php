<x-layouts.environment :environment="$this->environment">
    
    <x-section>
        <x-slot:title>Variables</x-slot:title>
        <x-slot:subheading>
            <div class="max-w-2xl">
                Environment variables store the configuration used by your apps.
                Add, edit, and rotate keys here to keep your environment consistent
                across deployments.
            </div>
        </x-slot:subheading>
        <x-slot:actions>
            <div class="flex gap-3">
                <flux:button
                    variant="ghost"
                    icon="arrow-down-tray"
                    x-on:click="$wire.dispatch('{{ \App\Environment\Livewire\EnvironmentDownloader::LAUNCH }}')">
                    Download...
                </flux:button>
                <flux:button
                    variant="ghost"
                    icon="arrow-up-tray"
                    x-on:click="$wire.dispatch('{{ \App\Environment\Livewire\EnvironmentImporter::LAUNCH }}')"
                    :disabled="!$this->canEditVariables">
                    Import...
                </flux:button>
            </div>
        </x-slot:actions>
        
        {{-- Add environment var form --}}
        @perform($this->environment, 'var:edit')
            <x-slot:form>
                <livewire:environment.variable.livewire.variable-creator :environment="$this->environment"/>
            </x-slot:form>
        @endperform
        
        {{-- Variable table display  --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column></flux:table.column>
                <flux:table.column 
                    sortable 
                    :sorted="$sortBy === 'key'" 
                    :direction="$sortDirection" 
                    wire:click="sort('key')">Key</flux:table.column>
                <flux:table.column>Value</flux:table.column>
                <flux:table.column
                    sortable 
                    :sorted="$sortBy === 'last_updated_at'" 
                    :direction="$sortDirection" 
                    wire:click="sort('last_updated_at')">Age</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->variables as $var)
                    @if ($var->is_deleted)
                        @include('environment.variable.table.row-tombstoned')
                    @else
                        @include('environment.variable.table.row-active')
                    @endif
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-section>
    
    {{-- Variable importer modal --}} 
    <livewire:environment.livewire.environment-importer :environment="$this->environment" />

    {{-- Environment downloader modal --}}
    <livewire:environment.livewire.environment-downloader :environment="$this->environment" />

    {{-- Variable editor modal --}}
    <livewire:environment.variable.livewire.variable-editor />
    
    {{-- Variable deleter modal --}}
    <livewire:environment.variable.livewire.variable-deleter />
    
    {{-- Variable reinstater modal --}}
    <livewire:environment.variable.livewire.variable-reinstater />
    
    {{-- Variable activity feed modal --}}
    <livewire:environment.variable.livewire.variable-activity-feed />

</x-layouts.environment>
