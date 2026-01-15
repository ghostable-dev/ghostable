<?php

namespace App\Auth\Livewire;

use App\Account\Models\User;
use App\Auth\Actions\LogAccountSecurityActivity;
use App\Auth\Actions\LogLoginActivity;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login()
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $user = User::where('email', $this->email)->first();

            app(LogLoginActivity::class)->failed(
                user: $user,
                email: $this->email,
                reason: 'invalid_credentials'
            );

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if ($user->isSuspended() || $user->isLocked()) {
            $message = $user->isSuspended()
                ? 'Your account is suspended.'
                : 'Your account is locked.';

            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            app(LogLoginActivity::class)->failed(
                user: $user,
                email: $user->email,
                reason: $user->isSuspended() ? 'suspended' : 'locked'
            );

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        if (! empty($user->two_factor_secret) && $user->two_factor_confirmed_at) {

            Auth::logout();

            session([
                'login.id' => $user->getKey(),
                'login.remember' => $this->remember,
            ]);

            app(LogAccountSecurityActivity::class)->mfaChallenge($user, [
                'source' => 'web',
            ]);

            $this->redirect(route('two-factor.login', absolute: false));

            return;
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        app(LogLoginActivity::class)->successful($user);

        if ($user->organizations()->count() > 1) {
            session(['show-organization-switcher' => true]);
        }

        $this->redirectIntended(
            default: route('dashboard', absolute: false),
            navigate: true
        );
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('auth.login')
            ->layout('components.layouts.auth', [
                'title' => 'Login',
                'canonical' => route('login'),
            ]);
    }
}
