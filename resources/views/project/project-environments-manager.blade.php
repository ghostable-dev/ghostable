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
                                <flux:link href="{{ route('environment.view', $env) }}">
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
                        <flux:callout>
                            <flux:callout.heading>
                                <flux:badge size="sm" class="mb-2">
                                    {{ $env->type->label() }}
                                </flux:badge>
                            </flux:callout.heading>
                            <flux:callout.heading>{{ $env->name }}</flux:callout.heading>
                            <x-slot name="actions">
                                <flux:link href="{{ route('environment.view', $env) }}">View</flux:link>
                            </x-slot>
                        </flux:callout>
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
    <flux:modal name="create-env" class="md:w-md">
        <form wire:submit="createEnvironment" class="space-y-6">
            <div>
                <flux:heading size="lg">Create Environment</flux:heading>
                <flux:text class="mt-2">Choose a name and environment type to add a new environment to this project.</flux:text>
            </div>
            <flux:input label="Name" wire:model="name" required />
            <flux:select label="Type" wire:model="type">
                @foreach($this->typeOptions as $key => $option)
                    <flux:select.option wire:key="type-{{ $key }}" value="{{ $key }}">
                        {{ $option }}
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

</div>