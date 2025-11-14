@push('meta')
    <x-seo-meta
        title="Verify your email"
        description="Please verify your email address to activate your Ghostable account."
        :keywords="[]"
        robots="noindex, nofollow, noarchive, noimageindex"
    />
@endpush

<div class="mt-4 flex flex-col gap-6">
    
    <div class="text-center">
        <flux:heading class="!text-5xl font-medium tracking-tighter text-pretty">
            Verify your email
        </flux:heading>
        <flux:subheading>
            Please verify your email address by clicking the verification link we just emailed to you.
        </flux:subheading>
    </div>

    @if (session('status') == 'verification-link-sent')
        <flux:text class="text-center font-medium !dark:text-green-400 !text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </flux:text>
    @endif

    <div class="flex flex-col items-center justify-between space-y-3">
        <flux:button wire:click="sendVerification" variant="primary" class="w-full">
            {{ __('Resend verification email') }}
        </flux:button>

        <flux:link class="text-sm cursor-pointer" wire:click="logout">
            {{ __('Log out') }}
        </flux:link>
    </div>
</div>
