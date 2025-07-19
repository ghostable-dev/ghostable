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
                    <flux:table.row wire:key="var-{{ $var }}">
                        <flux:table.cell>
                            <flux:text size="sm">{{ $var->key }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $secret = $var->isSecret();
                                $showingSecret = $this->showing[$var->id] ?? null;
                            @endphp
                            <flux:input 
                                value="{{ $showingSecret ? $var->value : $var->displayValue() }}"
                                :copyable="!$secret || $showingSecret"
                                readonly>
                                @if($secret)
                                    <x-slot name="iconTrailing">
                                        <flux:button 
                                            wire:click="toggleSecret('{{ $var->id }}')" 
                                            size="sm" 
                                            variant="subtle" 
                                            icon="{{ !$showingSecret ? 'eye' : 'eye-slash'}}" 
                                            class="-mr-1" />
                                    </x-slot>
                                @endif
                            </flux:input>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $var->latestVersion->version }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $var->last_updated_at->shortAbsoluteDiffForHumans() }}
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if($this->canEditVariables)
                            <flux:dropdown position="left">
                                <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                                <flux:menu>
                                    <flux:menu.item 
                                        wire:click="editVariable('{{ $var->id }}')">
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item 
                                        wire:click="confirmVariableRemoval('{{ $var->id }}')"
                                        variant="danger">
                                        Delete
                                    </flux:menu.item>
                                    <flux:separator/>
                                    <flux:menu.item 
                                        wire:click="viewVariableActivity('{{ $var->id }}')">
                                        Activity
                                    </flux:menu.item>
                                    @if($var->latestVersion->version > 1)
                                    <flux:menu.item 
                                        wire:click="viewVersions('{{ $var->id }}')">
                                        Versions
                                    </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        
    </x-section>
    
    {{-- Variable editor modal --}}
    <livewire:environment.livewire.environment-variable-editor />
        
    {{-- Variable activity feed modal --}}
    <livewire:environment.livewire.environment-variable-activity-feed />
    
    {{-- Variable activity feed modal --}}
    <livewire:environment.versioning.livewire.version-manager />
        
    {{-- Remove variable modal --}}
    <flux:modal name="confirm-variable-removal" class="md:w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove Variable</flux:heading>
                <flux:text class="mt-2">
                    Are you sure you want to remove the
                    <flux:text class="inline" variant="strong">
                        “{{ $this->variableToRemove?->key }}”
                    </flux:text>
                    key and corresponding value?
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <x-auth.confirms-password wire:then="removeVariable">
                    <flux:button  
                        variant="danger"
                        :loading="true"
                        wire:target="removeVariable">
                        {{ __('Remove Variable') }}
                    </flux:button>
                </x-auth.confirms-password>
            </div>
        </div>
    </flux:modal>
</div>