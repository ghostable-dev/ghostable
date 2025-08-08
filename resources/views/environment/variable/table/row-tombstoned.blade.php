<flux:table.row wire:key="var-{{ $var->id }}" class="opacity-60 italic">
    <flux:table.cell colspan="6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon.x-circle variant="solid" class="text-red-500" />
                <span>
                    <span class="font-medium text-red-600 dark:text-red-400">
                        “{{ $var->key }}”
                    </span> is disabled in this environment and will not be inherited.
                </span>
            </div>

            <div>
                <flux:dropdown>
                    <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                    <flux:menu>
                        <flux:menu.item 
                            wire:click="removeTombstone('{{ $var->id }}')"
                            variant="danger">
                            Restore Inherited Value
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </flux:table.cell>
</flux:table.row>