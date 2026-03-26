<x-layouts.app title="Device Setup Preview" :show-device-link-blocker="false">
    <div class="space-y-8">
        <div class="flex flex-col gap-4 rounded-[2rem] border border-dashed border-zinc-300 bg-zinc-100/80 px-6 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-2">
                <flux:badge variant="soft">Local preview</flux:badge>
                <flux:heading size="lg">Device setup onboarding preview</flux:heading>
                <flux:text class="max-w-2xl text-sm text-zinc-600">
                    This page mirrors the pre-organization dashboard state and opens the blocker automatically so you can review the UI without reproducing the real onboarding condition.
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-3">
                <flux:button variant="primary" x-on:click="$flux.modal('device-setup-blocker').show()">
                    Open modal again
                </flux:button>
                <flux:button variant="ghost" href="{{ route('dashboard') }}">
                    Back to dashboard
                </flux:button>
            </div>
        </div>

        <div class="space-y-6 py-10 text-center">
            <flux:heading size="md">{{ __('No organizations yet') }}</flux:heading>
            <flux:subheading>{{ __('Create an organization to get started.') }}</flux:subheading>
            <flux:modal.trigger name="create-organization">
                <flux:button variant="primary">{{ __('Create Organization') }}</flux:button>
            </flux:modal.trigger>
        </div>

        <livewire:organization.livewire.organization-create-modal />

        <x-cli-device-blocker />
    </div>
</x-layouts.app>
