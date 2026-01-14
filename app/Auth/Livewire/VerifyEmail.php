<?php

namespace App\Auth\Livewire;

use App\Auth\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Verify Email'])]
class VerifyEmail extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->isSuspended() || Auth::user()->isLocked()) {
            $message = Auth::user()->isSuspended()
                ? 'Your account is suspended.'
                : 'Your account is locked.';

            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            Session::flash('status', $message);

            $this->redirectRoute('login', navigate: true);

            return;
        }

        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('auth.verify-email');
    }
}
