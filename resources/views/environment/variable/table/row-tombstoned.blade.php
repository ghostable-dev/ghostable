<flux:table.row wire:key="var-{{ $var->id }}">
    <flux:table.cell>
        <flux:tooltip content="{{ $var->origin }}">
            <flux:button variant="subtle" icon="x-mark" size="xs"/>
        </flux:tooltip>
    </flux:table.cell>
    <flux:table.cell>
        <flux:text size="sm" class="line-through">{{ $var->key }}</flux:text>
    </flux:table.cell>
    <flux:table.cell colspan="2">
        <flux:text size="sm">disabled in this environment and will not be inherited.</flux:text>
    </flux:table.cell>
    <flux:table.cell align="end">
        <flux:dropdown position="left">
            <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
            <flux:menu>
                <flux:menu.item 
                    wire:click="reinstateVariable('{{ $var->id }}')"
                    variant="danger">
                    Restore Inherited Value
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:table.cell>
</flux:table.row>
