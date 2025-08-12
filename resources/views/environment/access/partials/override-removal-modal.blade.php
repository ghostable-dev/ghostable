<flux:modal name="confirm-override-removal" class="md:w-lg">
    <form wire:submit="removeOverride" class="space-y-6">
        <div>
            <flux:heading size="lg">Remove Permission Override</flux:heading>
            <flux:text class="mt-2">
                Are you sure you want to remove the
                <flux:text class="inline" variant="strong">
                    “{{ $this->overrideToRemove?->permission->label() }}”
                </flux:text>
                permission from
                <flux:text class="inline" variant="strong">
                    {{ $this->overrideToRemove?->user->email }}
                </flux:text>
                ?
            </flux:text>
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="danger">
                Remove Override
            </flux:button>
        </div>
    </form>
</flux:modal>