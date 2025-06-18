<div>
    <div class="mb-4">
        <flux:heading size="lg">{{ __('Team Members') }}</flux:heading>
        <flux:subheading>{{ __('View and manage your team.') }}</flux:subheading>
    </div>
    
    {{-- Current members table --}}
    <flux:table :paginate="$this->members">
        <flux:table.columns>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>2FA</flux:table.column>
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
                            {{ $member->teamMembership()->getMembershipForTeam($this->team)?->pivot->role->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($member->hasEnabledTwoFactorAuthentication())
                            <flux:icon.check-circle color="green"/>
                        @else
                            <flux:icon.exclamation-circle color="red"/>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell inset="top bottom" size="sm" align="right">
                        @if(auth()->user()->id !== $member->id)
                            <flux:dropdown class="max-w-32">
                                <flux:button 
                                    variant="ghost" 
                                    size="sm" 
                                    icon="ellipsis-horizontal">
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item 
                                        wire:click="manageMemberRole('{{ $member->id }}')">
                                        Manage role
                                    </flux:menu.item>
                                    <flux:menu.item 
                                        wire:click="confirmRemoveMember('{{ $member->id }}')"  
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
    
    {{-- Remove member modal --}}
    <flux:modal variant="bare" name="remove-member" class="min-w-[22rem]">
        <x-modal.form wire:submit="removeMember('{{ $this->memberToBeDeleted?->id }}')">
            <x-slot:title>Remove Team Member?</x-slot:title>
            <x-slot:description>
                <p class="text-wrap">
                    You're about to remove the team member 
                    <b class="text-black dark:text-white">{{ $this->memberToBeDeleted?->email }}</b>.
                </p>
            </x-slot:description>
            <x-slot:actions>
                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="danger">Remove</flux:button>
                </div>
            </x-slot:actions>
        </x-modal.form>
    </flux:modal>

    {{-- Manage member role modal --}}
    <flux:modal variant="bare" name="manage-member-role" class="min-w-[22rem]">
        <x-modal.form wire:submit="saveMemberRole">
            <x-slot:title>Manage Member Role</x-slot:title>
            <div class="space-y-6 mb-4">
                <flux:input value="{{ $this->managingRoleUser?->email }}" label="Member" readonly/>
                <x-team-role-select wire:model="managingRole"/>
            </div>
            <x-slot:actions>
                <div class="flex gap-3">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button 
                        type="submit" 
                        variant="primary"
                        wire:target="saveMemberRole">
                        Save changes
                    </flux:button>
                </div>
            </x-slot:actions>
        </x-modal.form>
    </flux:modal>
    
</div>