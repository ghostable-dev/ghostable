<div>

    <flux:callout icon="shield-check" variant="secondary">
        <flux:callout.heading>Add environment validation</flux:callout.heading>
        <flux:callout.text class="lg:max-w-4xl">
            Validation rules help enforce that critical environment variables 
            are present and correctly configured. If validation fails, Ghostable 
            can block CI deployments to protect your pipelines.
        </flux:callout.text>
        <x-slot name="actions">
            <flux:button 
                wire:click="launchCreateRuleModal"
                icon:trailing="plus">Add rule</flux:button>
        </x-slot>
    </flux:callout>
    
    <livewire:environment.livewire.environment-variable-rule-creator 
        :environment="$this->environment"/>

    <flux:table>
        <flux:table.columns>
            <flux:table.column 
                sortable 
                :sorted="$sortBy === 'key'" 
                :direction="$sortDirection" 
                wire:click="sort('key')">Key</flux:table.column>
            <flux:table.column>Rule</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->rules as $rule)
                <flux:table.row wire:key="rule-{{ $rule->id }}">
                    {{-- Key --}}
                    <flux:table.cell>
                        <flux:text>{{ $rule->key }}</flux:text>
                    </flux:table.cell>

                    {{-- Type & Constraints --}}
                    <flux:table.cell>
                        <div class="flex items-center gap-1 mb-1">
                            <flux:badge variant="secondary" size="sm">{{ $rule->type->label() }}</flux:badge>
                            @if($rule->is_required)
                                <flux:badge color="red" size="sm">Required</flux:badge>
                            @endif
                        </div>
                        <flux:text size="xs" class="text-gray-500">
                            @switch($rule->type->value)
                                @case('string')
                                    Length: {{ $rule->min_length ?? 0 }} – {{ $rule->max_length ?? '∞' }}
                                    @break

                                @case('integer')
                                    Value: {{ $rule->min_value  ?? '–∞' }} – {{ $rule->max_value  ?? '∞' }}
                                    @break

                                @case('enum')
                                    Allowed: {{ implode(', ', $rule->allowed_values) }}
                                    @break
                            @endswitch
                        </flux:text>
                    </flux:table.cell>

                    {{-- Description --}}
                    <flux:table.cell>
                        @if($rule->description)
                            <flux:text size="sm">{{ \Illuminate\Support\Str::limit($rule->description, 50) }}</flux:text>
                        @else
                            <flux:text size="xs" class="text-gray-400 italic">—</flux:text>
                        @endif
                    </flux:table.cell>

                    {{-- Actions --}}
                    <flux:table.cell align="end">
                        @if($this->canEditVariables)
                            <flux:dropdown>
                                <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                                <flux:menu>
                                    <flux:menu.item wire:click="editRule({{ $rule->id }})">
                                        Edit
                                    </flux:menu.item>
                                    <flux:menu.item 
                                        wire:click="confirmRuleRemoval({{ $rule->id }})" 
                                        variant="danger">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    
    
    {{-- Remove rule modal --}}
    <flux:modal name="confirm-rule-removal" class="md:w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove Rule</flux:heading>
                <flux:text class="mt-2">
                    Are you sure you want to remove the
                    <flux:text class="inline" variant="strong">
                        “{{ $this->ruleToRemove?->key }}”
                    </flux:text>
                    validation rule?
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button  
                    variant="danger"
                    wire:click="removeRule">
                    {{ __('Remove Rule') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>