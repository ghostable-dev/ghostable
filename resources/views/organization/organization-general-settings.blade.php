<section class="w-full">
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        <div>
            <flux:heading size="lg">{{ __('Organization Owner') }}</flux:heading>
            <div class="flex items-center gap-3 mt-4">
                <flux:profile
                    :initials="$this->organization->owner->initials()"
                    :chevron="false"
                    circle/>
                <span>
                    <b class="block text-black dark:text-white">{{ $this->organization->owner->name }}</b>
                    {{ $this->organization->owner->email }}
                </span>
                
            </div>
        </div>
        
        <div>
            <div>
                <flux:heading size="lg">{{ __('Organization Name') }}</flux:heading>
                <flux:subheading size="lg">{{ __('Update your organization\'s name.') }}</flux:subheading>
            </div>
            
            <form wire:submit="updateOrganizationName" class="my-6 w-full space-y-6">
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
    </x-layouts.organization-settings>
</section>
