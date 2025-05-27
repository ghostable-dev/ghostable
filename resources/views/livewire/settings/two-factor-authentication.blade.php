<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Two-factor authentication')" :subheading="__('Manage your two-factor authentication settings')">
        @if (! $enabled)
            <flux:text>{{ __('You have not enabled two-factor authentication.') }}</flux:text>
            <div class="mt-4">
                <flux:button wire:click="enableTwoFactorAuthentication" variant="primary">{{ __('Enable') }}</flux:button>
            </div>
        @else
            <flux:text>{{ __('Two-factor authentication is enabled for your account.') }}</flux:text>

            @if ($showingQrCode)
                <div class="mt-4" id="qr-code">
                    {!! $this->qrCode !!}
                </div>
                <div class="mt-4">
                    <flux:input wire:model="code" :label="__('Code from your authenticator')" />
                    <flux:button class="mt-2" wire:click="confirmTwoFactorAuthentication" variant="primary">{{ __('Confirm') }}</flux:button>
                </div>
            @endif

            @if ($showingRecoveryCodes)
                <div class="mt-4">
                    <flux:text class="block mb-2">{{ __('Store these recovery codes in a secure password manager.') }}</flux:text>
                    <ul class="grid gap-1 font-mono text-sm">
                        @foreach ($this->recoveryCodes as $recoveryCode)
                            <li>{{ $recoveryCode }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-4 flex gap-2">
                <flux:button wire:click="regenerateRecoveryCodes" variant="ghost">{{ __('Regenerate Recovery Codes') }}</flux:button>
                <flux:button wire:click="showRecoveryCodes" variant="ghost">{{ __('Show Recovery Codes') }}</flux:button>
                <flux:button wire:click="disableTwoFactorAuthentication" variant="danger">{{ __('Disable') }}</flux:button>
            </div>
        @endif
    </x-settings.layout>
</section>
