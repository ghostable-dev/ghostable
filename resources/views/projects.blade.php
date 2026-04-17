<x-layouts.app :title="__('Projects')">
    <x-slot name="subheader">
        @if(auth()->check() && auth()->user()->organizations->count())
            <div class="w-full bg-white pt-2">
                <div class="w-full px-6 lg:px-8">
                    <flux:navbar>
                        <flux:navbar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            Overview
                        </flux:navbar.item>
                        <flux:navbar.item :href="route('projects')" :current="request()->routeIs('projects')" wire:navigate>
                            Projects
                        </flux:navbar.item>
                        <flux:navbar.item
                            :href="route('organization.settings.general')"
                            :current="request()->routeIs('organization.settings.*')"
                            wire:navigate>
                            Settings
                        </flux:navbar.item>
                    </flux:navbar>
                </div>
            </div>
        @endif
    </x-slot>

    <div>
        {{-- Pending Invites --}}
        <livewire:account.livewire.pending-invites/>

        @if(auth()->user()->organizations->count())
            <livewire:project.livewire.organization-projects/>

            <livewire:organization.livewire.organization-switcher-modal/>
        @else
            <div class="space-y-6 text-center">
                <flux:heading size="md">{{ __('No organizations yet') }}</flux:heading>
                <flux:subheading>{{ __('Create an organization to get started.') }}</flux:subheading>
                <flux:modal.trigger name="create-organization">
                    <flux:button variant="primary">{{ __('Create Organization') }}</flux:button>
                </flux:modal.trigger>
            </div>

            <livewire:organization.livewire.organization-create-modal/>
        @endif
    </div>

</x-layouts.app>
