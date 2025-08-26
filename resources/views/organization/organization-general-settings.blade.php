<section class="w-full">
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        <div class="space-y-6 max-w-2xl">

            <x-section>
                <x-slot:title>{{ __('Organization Owner') }}</x-slot:title>
                <div class="flex items-center gap-3">
                    <flux:profile
                        :initials="$this->organization->owner->initials()"
                        :chevron="false"
                        circle/>
                    <span>
                        <b class="block text-black dark:text-white">{{ $this->organization->owner->name }}</b>
                        {{ $this->organization->owner->email }}
                    </span>
                </div>
            </x-section>

            <x-section>
                <x-slot:title>{{ __('Organization Name') }}</x-slot:title>
                <x-slot:subheading>{{ __('Update your organization\'s name.') }}</x-slot:subheading>
                <x-slot:actions>
                    @if($this->canEditName)
                        <div class="flex items-center justify-end gap-4">
                            <flux:button variant="primary" wire:click="updateOrganizationName" class="w-full">
                                {{ __('Save') }}
                            </flux:button>
                            <x-action-message class="me-3" on="name-updated">
                                {{ __('Saved.') }}
                            </x-action-message>
                        </div>
                    @endif
                </x-slot:actions>
                <form class="w-full space-y-6">
                    <flux:input wire:model="name" label="Name" :readonly="!$this->canEditName" required/>
                </form>
            </x-section>

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

        </div>

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
