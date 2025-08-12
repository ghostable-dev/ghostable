<flux:table.row wire:key="rule-{{ $rule->id }}">
    <flux:table.cell>
        <flux:text size="sm" class="line-through">{{ $rule->key }}</flux:text>
    </flux:table.cell>
    <flux:table.cell colspan="2">
        <flux:text size="sm">disabled in this environment and will not be enforced.</flux:text>
    </flux:table.cell>
    <flux:table.cell align="end">
        @if($this->canEditVariables)
            <flux:dropdown position="left">
                <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                <flux:menu>
                    <flux:menu.item
                        wire:click="reinstateRule('{{ $rule->id }}')"
                        variant="danger">
                        Restore Rule
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endif
    </flux:table.cell>
</flux:table.row>
