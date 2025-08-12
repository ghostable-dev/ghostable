<flux:table :paginate="$this->overrides">
    <flux:table.columns>
        <flux:table.column>Member</flux:table.column>
        <flux:table.column>Permission</flux:table.column>
        <flux:table.column></flux:table.column>
    </flux:table.columns>
    <flux:table.rows>
        @foreach($this->overrides as $override)
            <flux:table.row wire:key="override-{{ $override->id }}">
                <flux:table.cell class="flex items-center gap-3">
                    <flux:profile
                        :initials="$override->user->initials()"
                        :chevron="false"
                        circle/>
                    <span>
                        <b class="block text-black dark:text-white">
                            {{ $override->user->name }}
                        </b>
                        {{ $override->user->email }}
                    </span>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm">
                        {{ $override->permission->label() }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell inset="top bottom" size="sm" align="end">
                    <flux:link 
                        wire:click="confirmOverrideRemoval('{{ $override->id }}')"  
                        variant="danger">
                        Remove
                    </flux:link>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>   