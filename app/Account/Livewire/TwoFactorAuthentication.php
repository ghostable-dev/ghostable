<?php

namespace App\Account\Livewire;

use App\Account\Models\User;
use App\Auth\Concerns\ConfirmsPasswords;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TwoFactorAuthentication extends Component
{
    use ConfirmsPasswords;

    public bool $showingQrCode = false;

    public bool $showingConfirmation = false;

    public bool $showingRecoveryCodes = false;

    public string $code = '';

    public function mount(): void {}

    #[Computed()]
    public function enabled(): bool
    {
        return ! empty($this->user->two_factor_secret);
    }

    #[Computed()]
    public function user(): User
    {
        return Auth::user();
    }

    public function enableTwoFactorAuthentication(
        EnableTwoFactorAuthentication $enable
    ): void {
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
        return view('account.two-factor-authentication');
    }
}
