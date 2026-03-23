<?php

namespace App\Auth\Livewire;

use App\Account\Models\User;
use App\Auth\Actions\LogAccountSecurityActivity;
use App\Auth\Concerns\ConfirmsPasswords;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.shells.user-settings')]
class TwoFactorAuthentication extends Component
{
    use ConfirmsPasswords;

    public bool $showingQrCode = false;

    public bool $showingConfirmation = false;

    public bool $showingRecoveryCodes = false;

    public string $code = '';

    public function mount(): void
    {
        if (! empty($this->user->two_factor_secret) && empty($this->user->two_factor_confirmed_at)) {
            $this->showingQrCode = true;
            $this->showingConfirmation = true;
        }
    }

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
        $this->showingConfirmation = true;
    }

    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm): void
    {
        $confirm(Auth::user(), $this->code);

        Auth::user()->refresh();

        app(LogAccountSecurityActivity::class)->twoFactorEnabled(Auth::user());

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = true;
        $this->showingConfirmation = false;
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

        app(LogAccountSecurityActivity::class)->twoFactorDisabled(Auth::user());

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = false;
        $this->showingConfirmation = false;
    }

    public function getQrCodeProperty(): string
    {
        /** @var TwoFactorLoginResponse */
        return Auth::user()->twoFactorQrCodeSvg();
    }

    public function getRecoveryCodesProperty(): array
    {
        return Auth::user()->recoveryCodes();
    }

    public function render()
    {
        return view('auth.settings.two-factor-authentication');
    }
}
