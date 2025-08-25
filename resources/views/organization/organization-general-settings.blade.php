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
        
        {{-- @perform($this->organization, 'organization:manage-settings') --}}
            <flux:separator variant="subtle" />
            <div>
                <flux:callout icon="trash" color="red" inline>
                    <flux:callout.heading>Danger Zone</flux:callout.heading>
                    <flux:callout.text>
                        <p>Deleting your organization will permanently delete all its
                        projects, environments, secrets, and history.</p>
                        <p>Destructive settings that cannot be undone.</p>
                    </flux:callout.text>
                    <x-slot name="actions" class="@md:h-full m-0!">
                        <flux:modal.trigger name="confirm-organization-deletion">
                            <flux:button variant="danger">Delete Organization</flux:button>
                        </flux:modal.trigger>
                    </x-slot>
                </flux:callout>
            </div>
        {{-- @endperform --}}
        
        <flux:modal name="confirm-organization-deletion" focusable class="max-w-lg">
            <div class="space-y-6" x-data="{ confirmation: '' }">
                <div>
                    <flux:heading size="lg">
                        {{ __('Delete Organization') }}
                    </flux:heading>
                    <flux:subheading>
                        {{ __('This action will permanently delete the organization and all its
                        projects, environments, secrets, and history. This cannot be undone.') }}
                    </flux:subheading>
                </div>
                <div class="space-y-4">
                    <flux:text>
                        To confirm, please type
                        <flux:text class="inline font-bold" variant="strong">
                            {{ $this->organization->name }}
                        </flux:text>
                    </flux:text>
                    <flux:input x-model="confirmation" placeholder="{{ $this->organization->name }}"/>
                </div>
                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button
                        variant="danger"
                        x-bind:disabled="confirmation !== '{{ $this->organization->name }}'"
                        wire:click="deleteOrganization">
                        {{ __('Delete Organization') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
                
    </x-layouts.organization-settings>
</section>
