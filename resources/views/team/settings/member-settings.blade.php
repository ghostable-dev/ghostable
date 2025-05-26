<section class="w-full">
    @include('team.partials.team-settings-header')

    <x-layouts.team-settings>
        
        {{-- Current members display --}}
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Team Members') }}</flux:heading>
                <flux:subheading size="lg">
                    {{ __('View and manage your team.') }}
                </flux:subheading>
            </div>
            @include('team.partials.members-table')
        </div>
        
        @if(!$this->team->isPersonal())
        
            {{-- Invites Manager --}}
            <livewire:team.livewire.invites-manager/>
            
            {{-- Invite sender --}}
            <livewire:team.livewire.invite-sender/>
        
            {{-- Remove team member modal --}}
            <flux:modal variant="bare" name="remove-member" class="min-w-[22rem]">
                <x-modal.form wire:submit="removeMember('{{ $this->memeberToBeDeleted?->id }}')">
                    <x-slot:title>Remove Team Member?</x-slot:title>
                    <x-slot:description>
                        <p class="text-wrap">
                            You're about to remove the team member 
                            <b class="text-black dark:text-white">{{ $this->memeberToBeDeleted?->email }}</b>.
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
            
            {{-- Manage team member's role modal --}}
            <flux:modal variant="bare" name="manage-member-role" class="min-w-[22rem]">
                <x-modal.form wire:submit="saveMemberRole">
                    <x-slot:title>Manage Member Role</x-slot:title>
                    <div class="space-y-6 mb-4">
                        <flux:input value="{{ $this->managingRoleUser?->email }}" label="Member" readonly/>
                        <x-role-select wire:model="managingRole"/>
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
        
        @endif
        
    </x-layouts.team-settings>
</section>
