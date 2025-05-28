<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout 
        :heading="__('Two-factor authentication')" 
        :subheading="__('Add additional security to your account using two factor authentication')">
        <div class="space-y-6">
            
            {{-- @if ($this->enabled)
                @if ($showingConfirmation)
                    {{ __('Finish enabling two factor authentication.') }}
                @else
                    {{ __('You have enabled two factor authentication.') }}
                @endif
            @else
                {{ __('You have not enabled two factor authentication.') }}
            @endif --}}
            
            @if ($this->enabled)
                @if ($showingQrCode)
                    <flux:text>
                        @if ($showingConfirmation)
                            {{ __('To finish enabling two factor authentication, scan the following QR code using your phone\'s authenticator application or enter the setup key and provide the generated OTP code.') }}
                        @else
                            {{ __('Two factor authentication is now enabled. Scan the following QR code using your phone\'s authenticator application or enter the setup key.') }}
                        @endif
                    </flux:text>
                    
                    <div>
                        {!! $this->user->twoFactorQrCodeSvg() !!}
                        <flux:text variant="strong" class="mt-4">
                            {{ __('Setup Key') }}: <span>{{ decrypt($this->user->two_factor_secret) }}</span>
                        </flux:text>
                    </div>
                    
                    <div class="flex items-end gap-4">
                        <flux:input 
                            wire:model="code" 
                            :label="__('Code from your authenticator')" />
                        <flux:button 
                            class="mt-2" 
                            wire:click="confirmTwoFactorAuthentication" 
                            variant="primary">
                            {{ __('Confirm') }}
                        </flux:button>
                    </div>
                @endif
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
            
            <div class="flex gap-2">
                @if (! $this->enabled)
                    <x-auth.confirms-password wire:then="enableTwoFactorAuthentication">
                        <flux:button  
                            variant="primary"
                            :loading="true"
                            wire:target="enableTwoFactorAuthentication">
                            {{ __('Enable') }}
                        </flux:button>
                    </x-auth.confirms-password>
                @else
                    @if ($showingRecoveryCodes)
                        <x-auth.confirms-password wire:then="regenerateRecoveryCodes">
                            <flux:button 
                                icon="arrow-path"
                                :loading="true"
                                wire:target="regenerateRecoveryCodes">
                                {{ __('Regenerate Recovery Codes') }}
                            </flux:button>
                        </x-auth.confirms-password>
                    @elseif ($showingConfirmation)
                        <x-auth.confirms-password wire:then="confirmTwoFactorAuthentication">
                            <flux:button 
                                variant="primary"
                                :loading="true"
                                wire:target="confirmTwoFactorAuthentication">
                                {{ __('Confirm') }}
                            </flux:button>
                        </x-auth.confirms-password>
                    @else
                        <x-auth.confirms-password wire:then="showRecoveryCodes">
                            <flux:button 
                                :loading="true"
                                wire:target="showRecoveryCodes">
                                {{ __('Show Recovery Codes') }}
                            </flux:button>
                        </x-auth.confirms-password>
                    @endif
                    
                    @if ($showingConfirmation)
                        <x-auth.confirms-password wire:then="disableTwoFactorAuthentication">
                            <flux:button 
                                :loading="true"
                                wire:target="disableTwoFactorAuthentication">
                                {{ __('Cancel') }}
                            </flux:button>
                        </x-auth.confirms-password>
                    @else
                        <x-auth.confirms-password wire:then="disableTwoFactorAuthentication">
                            <flux:button  
                                :loading="true"
                                wire:target="disableTwoFactorAuthentication"
                                variant="danger">
                                {{ __('Disable') }}
                            </flux:button>
                        </x-auth.confirms-password>
                    @endif
                @endif
                
            </div>
        </div>
    </x-settings.layout>
</section>
