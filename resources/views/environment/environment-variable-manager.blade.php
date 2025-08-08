<div class="space-y-6">
    
    @if ($this->validationErrors->isNotEmpty())
        <flux:callout icon="exclamation-triangle" variant="warning">
            <flux:callout.heading>
                This environment has {{ $this->validationErrors->count() }} validation issue{{ $this->validationErrors->count() > 1 ? 's' : '' }}.
            </flux:callout.heading>
            <flux:callout.text>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($this->validationErrors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </flux:callout.text>
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
            <flux:button
                wire:click="downloadEnvFile"
                variant="ghost"
                icon="arrow-down-tray">
                Download .env
            </flux:button>
        </x-slot:actions>
        
        {{-- Add environment var form --}}
        @perform($this->environment, 'var:edit')
            <x-slot:form>
                <form wire:submit="addEnvironmentVariable" class="flex flex-inline items-end gap-4">
                    <div class="basis-1/2 grow-0">
                        <x-environment-key-autocomplete
                            wire:model.live="key" 
                            label="Key" 
                            placeholder="e.g. PARANORMAL_STATUS"
                            required
                            :groupedSuggestions="$this->keySuggestions"/>
                    </div>
                    <div class="basis-1/2 grow-0">
                        <flux:autocomplete 
                            wire:model.live="value" 
                            label="Value" 
                            placeholder="{{ empty($this->key) ? 'we_got_one' : '' }}"
                            required>
                            @foreach($this->valueSuggestions as $suggestion)
                                <flux:autocomplete.item wire:key="value-{{ $suggestion }}">
                                    {{ $suggestion }}
                                </flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                    </div>
                    <div class="flex-none">
                        <flux:button 
                            type="submit" 
                            variant="primary" 
                            icon:trailing="plus">
                            Add Variable
                        </flux:button>
                    </div>
                </form>
                <flux:text variant="subtle" class="mt-4 flex flex-inline gap-1">
                    @if($this->keyDescription)
                        <flux:icon.information-circle variant="mini"/>
                        <span>{{ $this->keyDescription }}</span>
                    @else
                        Define a new key-value pair in this environment.
                    @endif
                </flux:text>
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
                        @include('environment.variables.table.row-tombstoned')
                    @else
                        @include('environment.variables.table.row-active')
                    @endif
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-section>
    
    {{-- Variable editor modal --}}
    <livewire:environment.livewire.environment-variable-editor />
    
    {{-- Variable deleter modal --}}
    <livewire:environment.livewire.environment-variable-deleter />
        
    {{-- Variable activity feed modal --}}
    <livewire:environment.livewire.environment-variable-activity-feed />
    
    {{-- Variable activity feed modal --}}
    <livewire:environment.versioning.livewire.version-manager />
    
</div>