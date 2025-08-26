<div class="space-y-6 max-w-4xl">
    
    <x-section>
        <x-slot:title>{{ __('Organization Members') }}</x-slot:title>
        <x-slot:subheading>
            {{ __('View and manage your organization.') }}
        </x-slot:subheading>
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
                                {{ $member->organizationMembership()->getMembershipForOrganization($this->organization)?->pivot->role->label() }}
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
    </x-section>
    
    {{-- Remove member modal --}}
    @include('organization.partials.remove-member-modal')

    {{-- Manage member role modal --}}
    @include('organization.partials.manage-member-role-modal')
    
</div>