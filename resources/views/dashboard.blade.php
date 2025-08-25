<x-layouts.app :title="__('Dashboard')">

    {{-- Pending Invites --}}
    <livewire:account.livewire.pending-invites/>

    @if(auth()->user()->organizations->count())
        <livewire:project.livewire.organization-projects/>

        <livewire:organization.livewire.organization-switcher-modal/>
    @else
        <div class="space-y-6">
            <flux:heading size="md">{{ __('No organizations yet') }}</flux:heading>
            <flux:subheading>{{ __('Create an organization to get started.') }}</flux:subheading>
            <flux:modal.trigger name="create-organization">
                <flux:button variant="primary">{{ __('Create Organization') }}</flux:button>
            </flux:modal.trigger>
        </div>

        <livewire:organization.livewire.organization-create-modal/>
    @endif

</x-layouts.app>

