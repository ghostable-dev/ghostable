<flux:modal name="manage-member-role" class="min-w-[22rem]">
    <form wire:submit="saveMemberRole">
        <flux:heading size="lg">Manage Member Role</flux:heading>
        <div class="space-y-6 mb-4">
            <flux:input value="{{ $this->managingRoleUser?->email }}" label="Member" readonly/>
            <x-organization-role-select wire:model="managingRole"/>
        </div>
        <div class="flex gap-2">
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
    </form>
</flux:modal>