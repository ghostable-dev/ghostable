@push('meta')
    <x-seo-meta
        title="Two-factor authentication"
        description="Enter your authentication code to securely access your Ghostable account."
        :keywords="[]"
        robots="noindex, nofollow, noarchive, noimageindex"
    />
@endpush

<x-layouts.auth title="Two-factor authentication">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Two-factor Authentication')"
            :description="__('Please confirm access to your account by entering the authentication code provided by your authenticator application.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ url('/two-factor-challenge') }}" class="flex flex-col gap-6">
            @csrf
            <flux:input
                name="code"
                :label="__('Authentication code')"
                inputmode="numeric"
                autofocus
                autocomplete="one-time-code"
                placeholder="123456"
                required
            />

            <flux:input
                name="recovery_code"
                :label="__('Recovery code')"
                autocomplete="one-time-code"
                placeholder="xxxxxxxx"
            />

            <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
        </form>
    </div>
</x-layouts.auth>