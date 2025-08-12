<flux:modal wire:model="showing" class="md:w-xl">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $this->isLocalToTarget ? 'Update' : 'Override' }} Validation Rule
            </flux:heading>
        </div>

        <form wire:submit="update" class="space-y-6">
            <div>
                <flux:switch 
                    label="Is Required?" 
                    wire:model.live="is_required"
                    description="Ensures this variable is present in this environment." />
            </div>

            <div class="flex flex-inline items-end gap-4">
                <div class="basis-1/2 grow-0">
                    <flux:input
                        wire:model.live="key" 
                        label="Key"
                        readonly
                        icon:trailing="lock-closed"
                        placeholder="e.g. APP_DEBUG"
                        required
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

            {{-- String --}}
            @if ($this->type?->value === 'string')
                <div class="grid grid-cols-2 gap-4">
                    <flux:input 
                        type="number"
                        label="Min Length" 
                        wire:model.defer="min" 
                        placeholder="Optional"
                        min="0" />
                    <flux:input 
                        type="number"
                        label="Max Length" 
                        wire:model.defer="max" 
                        placeholder="Optional"
                        min="0" />
                </div>
            @endif

            {{-- Integer --}}
            @if ($this->type?->value === 'integer')
                <div class="grid grid-cols-2 gap-4">
                    <flux:input 
                        type="number"
                        wire:model.defer="min"
                        label="Min Value"
                        placeholder="Optional" />
                    <flux:input 
                        type="number"
                        wire:model.defer="max"
                        label="Max Value"
                        placeholder="Optional" />
                </div>
            @endif

            {{-- Enum --}}
            @if ($this->type?->value === 'enum')
                <div>
                    <x-tag-input wire:model.defer="allowed_values" label="Allowed Values" />
                </div>
            @endif

            {{-- Description --}}
            <div>
                <flux:input 
                    wire:model.defer="description"
                    label="Description (optional)"
                    placeholder="Describe why this rule is needed" />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                @if ($this->noChangesWereMade)
                    <flux:button
                        variant="primary"
                        type="submit">
                        {{ $this->isLocalToTarget ? 'Update' : 'Override' }}
                    </flux:button>
                @else
                    <flux:button
                        variant="primary"
                        wire:click="update">
                        {{ $this->isLocalToTarget ? 'Update' : 'Override' }}
                    </flux:button>
                @endif
            </div>
        </form>
    </div>
</flux:modal>