<flux:table :paginate="$this->members">
    <flux:table.columns>
        <flux:table.column>User</flux:table.column>
        <flux:table.column>Role</flux:table.column>
        <flux:table.column></flux:table.column>
    </flux:table.columns>
    <flux:table.rows>
        @foreach($this->members as $member)
            <flux:table.row wire:key="member-{{ $member->id }}">
                <flux:table.cell class="flex items-center gap-3">
                    <flux:profile
                        :initials="$member->initials()"
                        :chevron="false"
                        circle/>
                    <span>
                        <b class="block text-black dark:text-white">{{ $member->name }}</b>
                        {{ $member->email }}
                    </span>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm">
                        {{ $member->roleForTeam($this->team)?->name }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell inset="top bottom" size="sm" align="right">
                    @if(auth()->user()->id !== $member->id)
                        <flux:dropdown class="max-w-32">
                            <flux:button 
                                variant="ghost" 
                                size="sm" 
                                icon="ellipsis-horizontal" 
                                inset="top bottom">
                            </flux:button>
                            <flux:menu>
                                <flux:menu.item 
                                    wire:click="manageMemberRole('{{ $member->id }}')" 
                                    icon="pencil-square">
                                    Manage role
                                </flux:menu.item>
                                <flux:menu.item 
                                    wire:click="confirmRemoveMember('{{ $member->id }}')" 
                                    icon="trash" 
                                    variant="danger">
                                    Remove
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>