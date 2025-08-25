<flux:modal name="remove-member" class="md:w-lg">
    <form wire:submit="removeMember('{{ $this->memberToBeDeleted?->id }}')" class="space-y-6">
        <div>
            <flux:heading size="lg">Remove Organization Member?</flux:heading>
            <flux:text class="mt-2">
                 You're about to remove the organization member 
                <flux:text class="inline" variant="strong">{{ $this->memberToBeDeleted?->email }}</flux:text>.
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="danger">
                Remove Member
            </flux:button>
        </div>
    </form>
</flux:modal>