<?php

namespace App\Auth\Livewire;

use App\Account\Models\User;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $this->email)->first();

        if ($user?->isSuspended()) {
            session()->flash('status', __('A reset link will be sent if the account exists.'));

            return;
        }

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }

    public function render()
    {
        return view('auth.forgot-password')
            ->layout('components.layouts.auth', [
                'title' => 'Forgot Password?',
                'canonical' => route('password.request'),
            ]);
    }
}
