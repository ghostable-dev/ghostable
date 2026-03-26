<x-layouts.app :title="__('Dashboard')">
    @php
        $googleTagId = config('services.google_tag.id');
        $accountCreatedLabel = config('services.google_tag.account_created_label');
        $shouldTrackAccountCreated = request()->boolean('account_created')
            && filled($googleTagId)
            && filled($accountCreatedLabel)
            && auth()->check()
            && auth()->user()->hasVerifiedEmail();
    @endphp

    @if($shouldTrackAccountCreated)
        @include('components.google-tag.script', [
            'id' => $googleTagId,
            'event' => 'conversion',
            'payload' => [
                'send_to' => "{$googleTagId}/{$accountCreatedLabel}",
                'transaction_id' => 'account-created-'.auth()->id(),
            ],
        ])
    @endif

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

</x-layouts.app>
