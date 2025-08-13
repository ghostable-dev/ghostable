<x-layouts.environment :environment="$this->environment">
    
    @if ($this->validationErrors->isNotEmpty())
    <flux:callout icon="exclamation-triangle" variant="warning">
        <flux:accordion transition>
            <flux:accordion.item>
                <flux:accordion.heading>
                    <flux:callout.heading>
                        This environment has {{ $this->validationErrors->count() }} validation issue{{ $this->validationErrors->count() > 1 ? 's' : '' }}.
                    </flux:callout.heading>
                </flux:accordion.heading>
                <flux:accordion.content>
                    <flux:callout.text>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($this->validationErrors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </flux:callout.text>
                </flux:accordion.content>
            </flux:accordion.item>
        </flux:accordion>
    </flux:callout>
    @endif

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
                <flux:button variant="ghost" icon="arrow-down-tray" wire:click="downloadEnvFile"></flux:button>
                <flux:button variant="ghost" icon="arrow-up-tray" wire:click="$set('showImportModal', true)"></flux:button>
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
                <flux:table.column>Version</flux:table.column>
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

    <flux:modal wire:model="showImportModal" class="md:w-lg">
        <x-modal.form wire:submit="importEnvFile">
            <x-slot:title>Import Environment File</x-slot:title>

            <div class="space-y-6 mb-4">
                <flux:textarea wire:model.defer="envInput" rows="12" label="Env file contents" />
            </div>

            <x-slot:actions>
                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Import</flux:button>
                </div>
            </x-slot:actions>
        </x-modal.form>
    </flux:modal>

    {{-- Variable editor modal --}}
    <livewire:environment.variable.livewire.variable-editor />
    
    {{-- Variable deleter modal --}}
    <livewire:environment.variable.livewire.variable-deleter />
    
    {{-- Variable reinstater modal --}}
    <livewire:environment.variable.livewire.variable-reinstater />
        
    {{-- Variable activity feed modal --}}
    <livewire:environment.variable.livewire.variable-activity-feed />
    
    {{-- Variable activity feed modal --}}
    <livewire:environment.versioning.livewire.version-manager />

</x-layouts.environment>
