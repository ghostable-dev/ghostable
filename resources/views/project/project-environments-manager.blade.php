<div>
    <flux:tab.group>
        
        {{-- Header --}}
        <div class="flex items-center justify-between flex-wrap gap-4 mb-4">
            @perform($this->project, 'env:create')
                <flux:modal.trigger name="create-env">
                    <flux:button variant="primary">
                        Create Environment
                    </flux:button>
                </flux:modal.trigger>
            @endperform
            <flux:tabs wire:model="tab" variant="segmented">
                <flux:tab icon="list-bullet" name="list">List</flux:tab>
                <flux:tab icon="squares-2x2" name="board">Board</flux:tab>
            </flux:tabs>
        </div>
        
        {{-- Environment list view display --}}
        <flux:tab.panel name="list">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->environments as $env)
                        <flux:table.row wire:key="list-row-{{ $env->id }}">
                            <flux:table.cell>{{ $env->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge>{{ $env->type->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:link href="{{ route('environment.variables', $env) }}">
                                    View
                                </flux:link>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>
        
        {{-- Environment board view display --}}
        <flux:tab.panel name="board">
            <ul 
                role="list" 
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                @foreach($this->environments as $env)
                    <li class="col-span-1" wire:key="board-{{ $env->id }}">
                        <x-environment.display-card :$env/>
                        {{-- <flux:callout>
                            <flux:callout.heading>
                                @if($env->base_id)
                                    <flux:badge color="blue" icon="git-branch" size="sm" class="mb-2">
                                        {{ $env->base->name }}
                                    </flux:badge>
                                @endif
                                <flux:badge size="sm" class="mb-2">
                                    {{ $env->type->label() }}
                                </flux:badge>
                            </flux:callout.heading>
                            <flux:callout.heading>{{ $env->name }}</flux:callout.heading>
                            <x-slot name="actions">
                                <flux:link href="{{ route('environment.variables', $env) }}">View</flux:link>
                            </x-slot>
                        </flux:callout> --}}
                    </li>
                @endforeach
            </ul>
        </flux:tab.panel>
        
    </flux:tab.group>
    
    {{-- Pagination --}}
    {{-- <div class="mt-6">
        <flux:pagination :paginator="$this->environments" />
    </div> --}}
    
    {{-- Create environment modal --}}
    <flux:modal name="create-env" class="max-w-full md:w-[36rem]">
        <form wire:submit="createEnvironment" class="flex-1 space-y-6">
            <div>
                <flux:heading size="lg">Create Environment</flux:heading>
                {{-- <flux:text class="mt-2">Set up a new environment to securely manage and share your variables. Optionally link it to a base environment to inherit and override settings automatically.</flux:text> --}}
            </div>
            <div>
                <div class="flex items-start gap-4">
                    <div class="flex-none">
                        <flux:select 
                            variant="listbox" 
                            label="Type" 
                            wire:model.live="type">
                            @foreach($this->typeOptions as $key => $option)
                                <flux:select.option wire:key="type-{{ $key }}" value="{{ $key }}">
                                    {{ $option }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="flex-1">
                        <flux:autocomplete 
                            class="w-auto"
                            label="Name" 
                            wire:model.live.debounce.350ms="name" 
                            required>
                             @foreach ($this->nameSuggestions as $suggestion)
                                <flux:autocomplete.item 
                                    wire:key="name-suggestion-{{ $suggestion }}">
                                    {{ $suggestion }}
                                </flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                    </div>
                </div>
                <flux:description class="mt-3">
                    Must be unique within this project. Use lowercase letters, numbers, and dashes.
                </flux:description>
            </div>
            
            <flux:select 
                variant="listbox"
                wire:model="base_id"
                label="Base Environment"
                searchable
                description-trailing="Dynamically link from a base environment, with support for overrides and custom variables.">
                <flux:select.option wire:key="base-none" value="">
                    None (standalone)
                </flux:select.option>
                @foreach($this->environments as $env)
                    <flux:select.option 
                        wire:key="base-{{ $env->id }}" 
                        value="{{ $env->id }}">
                        <x-icon.git-branch class="inline size-4 mr-2 opacity-60"/> {{ $env->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    Create Environment
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="upgrade-environment-limit" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Upgrade Required</flux:heading>
                <flux:text class="mt-2">Environment limit reached for this project. Upgrade to create more environments.</flux:text>
            </div>
            <div class="flex justify-end">
                <flux:button variant="primary">Upgrade Plan</flux:button>
            </div>
        </div>
    </flux:modal>

</div>