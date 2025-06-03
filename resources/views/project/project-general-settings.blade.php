<section class="w-xl space-y-12">
    
    <div>
        <div class="mb-4">
            <flux:heading size="lg">{{ __('Project ID') }}</flux:heading>
            <flux:subheading>Your project's unique ID.</flux:subheading>
        </div>
        <div>
            <flux:input wire:model="projectId" readonly copyable/>
        </div>
    </div>
        
    <div>
        <div>
            <flux:heading size="lg">{{ __('Project Name') }}</flux:heading>
            <flux:subheading>{{ __('Update your projects\'s name.') }}</flux:subheading>
        </div>
        <form wire:submit="updateName" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" label="Name" :readonly="!$this->canEditName" required/>
            <div class="flex items-center gap-4">
                @if($this->canEditName)
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full">
                            {{ __('Save') }}
                        </flux:button>
                    </div>
                @endif
                <x-action-message class="me-3" on="name-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </div>
        
</section>
