<div>
    <div class="mb-4 flex">
        <div>
            <flux:heading size="lg">{{ __('Pending Invites') }}</flux:heading>
            <flux:subheading>{{ __('Organization members who haven’t joined yet.') }}</flux:subheading>
        </div>
        <flux:spacer/>
        @if($this->pendingInvites->isNotEmpty())
        <flux:modal.trigger name="send-invite">
            <flux:button variant="primary">
                Invite member
            </flux:button>
        </flux:modal.trigger>
        @endif
    </div>
    
    {{-- Send invite modal --}}
    <flux:modal variant="bare" name="send-invite" class="min-w-[22rem]">
        <x-modal.form wire:submit="sendInvite">
             <x-slot:title>
                Invite Organization Member
            </x-slot:title>
            <x-slot:description>
                <p class="text-wrap">You're about to remove the invite sent to. Please select the appropriate role and enter the email of the person you’d like to invite to your organization.</p>
            </x-slot:description>
            <div class="space-y-6 mb-4">
                <flux:input type="email" label="Email" required wire:model="email"/>
                <x-organization-role-select wire:model="role"/>
            </div>
            <x-slot:actions>
                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Invite member</flux:button>
                </div>
            </x-slot:actions>
        </x-modal.form>
        
    </flux:modal>
    
    {{-- Invites display --}}
    @if($this->pendingInvites->isNotEmpty())
    
        {{-- Pending table --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column>To</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column class="hidden sm:block">Sent By</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->pendingInvites as $invite)
                    <flux:table.row wire:key="invite-{{ $invite->id }}">
                        <flux:table.cell variant="strong">
                            {{ $invite->email }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge inset="top bottom" size="sm">
                                {{ $invite->role->label() }}
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
        
        {{-- Delete invite modal --}}
        <flux:modal variant="bare" name="delete-invite" class="min-w-[22rem]">
            <x-modal.form wire:submit="deleteInvite">
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
    @else
        
        {{-- Invites empty state --}}
        <div class="space-y-6">
            <div>
                <flux:subheading>You haven’t invited anyone yet. Once you do, they’ll show up here until they accepted or expire.</flux:subheading>
            </div>
            <flux:modal.trigger name="send-invite">
                <flux:button variant="primary">
                    Invite member
                </flux:button>
            </flux:modal.trigger>
        </div>
        
    @endif
    
</div>