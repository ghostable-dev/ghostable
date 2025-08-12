<flux:modal wire:model="showing" class="md:w-lg">
    <div class="space-y-6">
        <div class="space-y-4">
            <flux:heading size="lg">Remove Rule</flux:heading>

            @if ($this->isLocalToTarget)
                @if ($this->isOverride)
                    <flux:radio.group wire:model.live="deleteMode" label="Choose what to do with this rule">
                        <flux:radio value="delete" label="Delete (fall back to inherited rule)" />
                        <flux:radio value="suppress" label="Suppress in this environment" />
                    </flux:radio.group>

                    @switch($this->deleteMode)
                        @case('delete')
                            <flux:text class="mt-2">
                                This will delete the overridden rule and restore the inherited rule for
                                <flux:text class="inline" variant="strong">“{{ $this->rule?->key }}”</flux:text>
                                from the parent environment.
                            </flux:text>
                            @break

                        @case('suppress')
                            <flux:text class="mt-2">
                                This will suppress the
                                <flux:text class="inline" variant="strong">“{{ $this->rule?->key }}”</flux:text>
                                rule in this environment, even if it exists in a parent environment.
                            </flux:text>
                            @break
                    @endswitch
                @else
                    <flux:text class="mt-2">
                        This will permanently delete the rule
                        <flux:text class="inline" variant="strong">“{{ $this->rule?->key }}”</flux:text>
                        from this environment. There is no inherited rule to fall back to.
                    </flux:text>
                @endif
            @else
                <flux:text class="mt-2">
                    This rule is inherited from
                    <flux:text class="inline" variant="strong">“{{ $this->rule?->environment->name }}”</flux:text>.
                    You can suppress it to prevent it from being enforced in this environment.
                </flux:text>

                <flux:text class="mt-2">
                    This will prevent the rule
                    <flux:text class="inline" variant="strong">“{{ $this->rule?->key }}”</flux:text>
                    from being inherited or used in this environment.
                </flux:text>
            @endif
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button
                variant="danger"
                wire:click="removeRule">
                {{ match($this->deleteMode) {
                    'delete' => 'Delete',
                    'suppress' => 'Suppress',
                    default => 'Confirm',
                } }}
            </flux:button>
        </div>
    </div>
</flux:modal>
