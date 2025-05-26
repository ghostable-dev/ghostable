<div @class(['hidden' => $this->pendingInvites->isEmpty()])>
    @if($this->pendingInvites->isNotEmpty())
        <flux:card class="mb-8">
            <div>
                <flux:heading size="lg">Pending Invites</flux:heading>
                <flux:subheading>Manage team invitations that haven’t been accepted yet. You can resend or delete them at any time.</flux:subheading>
            </div>
            <flux:table size="sm" class="mt-6">
                <flux:table.columns>
                    <flux:table.column>To</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column class="hidden sm:block">Sent By</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->pendingInvites as $invite)
                        <flux:table.row>
                            <flux:table.cell variant="strong">
                                {{ $invite->email }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$invite->status->color()" inset="top bottom" size="sm">
                                    {{ $invite->status->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge inset="top bottom" size="sm">
                                    {{ $invite->role->name }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="hidden sm:block">
                                {{ $invite->user?->email ?? 'n/a' }}
                            </flux:table.cell>
                            <flux:table.cell inset="top bottom" size="sm" align="right">
                                <flux:dropdown class="max-w-32">
                                    <flux:button 
                                        variant="ghost" 
                                        size="sm" 
                                        icon="ellipsis-horizontal" 
                                        inset="top bottom">        
                                    </flux:button>
                                    <flux:menu>
                                        @if(!$invite->sentRecently())
                                            <flux:menu.item 
                                                wire:click="resendInvite('{{ $invite->id }}')" 
                                                icon="paper-airplane">
                                                Resend
                                            </flux:menu.item>
                                        @endif
                                        <flux:menu.item 
                                            wire:click="confirmDeleteInvite('{{ $invite->id }}')" 
                                            icon="trash" 
                                            variant="danger">
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
        
        <flux:modal variant="bare" name="delete-invite" class="min-w-[22rem]">
            <x-modal.form wire:submit="deleteInvite('{{ $this->inviteToBeDeleted?->id }}')">
                <x-slot:title>
                    Delete Invite?
                </x-slot:title>
                <x-slot:description>
                    <p class="text-wrap">You're about to remove the invite sent to <b class="text-black dark:text-white">{{ $this->inviteToBeDeleted?->email }}</b>.</p>
                    <p class="text-wrap">This action can't be undone, and any previous notifications will be invalidated.</p>
                </x-slot:description>
                <x-slot:actions>
                    <div class="flex gap-3">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="danger">Delete</flux:button>
                    </div>
                </x-slot:actions>
            </x-modal.form>
        </flux:modal>
    @endif
</div>