<div>
    <flux:card class="space-y-6">
        <div>
            <flux:heading size="lg">Invite Team Member</flux:heading>
            <flux:subheading>Please select the appropriate role and enter the 
            email of the person you’d like to invite to your team.</flux:subheading>
        </div>
        <form class="space-y-6" wire:submit="createInvite">
            <flux:input type="email" label="Email" required wire:model="emailToInvite"/>
            <x-role-select wire:model="roleToInvite"/>
            <flux:button type="submit" variant="primary">
                Send invite
            </flux:button>
        </form>
    </flux:card>
</div>