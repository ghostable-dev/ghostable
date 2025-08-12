<flux:modal wire:model="showing" class="md:w-lg">
    <div class="space-y-6">
        <div class="space-y-4">
            <flux:heading size="lg">Restore Rule</flux:heading>

            @if ($this->isOverride)
                <flux:text class="mt-2">
                    This will reinstate the override for
                    <flux:text class="inline" variant="strong">“{{ $this->rule?->key }}”</flux:text>
                    in this environment.
                </flux:text>
            @else
                <flux:text class="mt-2">
                    This will remove the block on
                    <flux:text class="inline" variant="strong">“{{ $this->rule?->key }}”</flux:text>
                    so the rule is inherited from the parent environment.
                </flux:text>
            @endif
        </div>

        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button
                variant="primary"
                wire:click="reinstateRule">
                Reinstate
            </flux:button>
        </div>
    </div>
</flux:modal>
