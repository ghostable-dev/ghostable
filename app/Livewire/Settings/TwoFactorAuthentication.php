<?php

namespace App\Livewire\Settings;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Component;

class TwoFactorAuthentication extends Component
{
    public bool $enabled = false;
    public bool $showingQrCode = false;
    public bool $showingRecoveryCodes = false;
    public string $code = '';

    public function mount(): void
    {
        $this->enabled = ! is_null(Auth::user()->two_factor_secret);
    }

    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable): void
    {
        $enable(Auth::user());

        $this->showingQrCode = true;
    }

    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm): void
    {
        $confirm(Auth::user(), $this->code);

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = true;
        $this->enabled = true;
        $this->reset('code');
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $generate(Auth::user());

        $this->showingRecoveryCodes = true;
    }

    public function showRecoveryCodes(): void
    {
        $this->showingRecoveryCodes = true;
    }

    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable): void
    {
        $disable(Auth::user());

        $this->enabled = false;
        $this->showingRecoveryCodes = false;
    }

    public function getQrCodeProperty(): string
    {
        /** @var \Laravel\Fortify\Contracts\TwoFactorLoginResponse */
        return Auth::user()->twoFactorQrCodeSvg();
    }

    public function getRecoveryCodesProperty(): array
    {
        return Auth::user()->recoveryCodes();
    }

    public function render()
    {
        return view('livewire.settings.two-factor-authentication');
    }
}
