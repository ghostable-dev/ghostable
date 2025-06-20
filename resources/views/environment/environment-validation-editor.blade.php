<div>
    <div class="lg:max-w-4xl">
        <form wire:submit="addRule" class="space-y-6">
            <div>
                <flux:switch 
                    label="Is Required?" 
                    wire:model.live="is_required"
                />
            </div>
            <div class="flex flex-inline items-end gap-4">
                <div class="basis-1/2 grow-0">
                    <x-environment-key-autocomplete
                        wire:model.live="key" 
                        label="Key" 
                        placeholder="e.g. APP_DEBUG"
                        required
                        :groupedSuggestions="$this->keySuggestions"
                    />
                </div>
                <div class="basis-1/2 grow-0">
                    <flux:select 
                        label="Type" 
                        wire:model.live="type">
                        @foreach($this->ruleTypeOptions as $type)
                            <flux:select.option value="{{ $type->value }}">
                                {{ $type->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            {{-- 👇 Type-Specific Fields --}}
            @if ($this->type->value === 'string')
                <div class="grid grid-cols-2 gap-4">
                    <flux:input 
                        type="number"
                        label="Min Length" 
                        wire:model.defer="minLength" 
                        placeholder="Optional"
                    />
                    <flux:input 
                        type="number"
                        label="Max Length" 
                        wire:model.defer="maxLength" 
                        placeholder="Optional"
                    />
                </div>
            @endif
            
            @if ($this->type->value === 'integer')
                <div class="grid grid-cols-2 gap-4">
                    <flux:input 
                        type="number"
                        wire:model.defer="minValue"
                        label="Min Value"
                        placeholder="Optional"
                    />
                    <flux:input 
                        type="number"
                        wire:model.defer="maxValue"
                        label="Max Value"
                        placeholder="Optional"
                    />
                </div>
            @endif

            @if ($this->type->value === 'enum')
                <div>
                    <x-tag-input wire:model.defer="allowed_values" label="Allowed Values" />
                </div>
            @endif

            @if ($this->type->value === 'regex')
                <div>
                    <flux:input 
                        wire:model.defer="regex"
                        label="Regex Pattern" 
                        placeholder="/^[A-Z]+$/"
                    />
                </div>
            @endif

            <div>
                <flux:input 
                    wire:model.defer="description"
                    label="Description (optional)"
                    placeholder="Describe why this rule is needed" 
                />
            </div>

            <div>
                <flux:button 
                    variant="primary"
                    type="submit"
                >
                    Add
                </flux:button>
            </div>
        </form>
    </div>
    
    
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
                    <flux:table.cell>
                        <flux:text size="sm">{{ $rule->key }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge>{{ $rule->rule }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @if($this->canEditVariables)
                        <flux:dropdown>
                            <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                            <flux:menu>
                                <flux:menu.item 
                                    wire:click="editRule('{{ $rule->id }}')">
                                    Edit
                                </flux:menu.item>
                                <flux:menu.item 
                                    wire:click="confirmRuleRemoval('{{ $rule->id }}')"
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