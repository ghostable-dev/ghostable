<?php

namespace App\Account\Livewire;

use App\Account\Actions\RegisterUser;
use App\Account\Models\User;
use App\Account\Rules\UserRules;
use App\Auth\Enums\CliLoginSessionStatus;
use App\Auth\Models\CliLoginSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Sign up'])]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $terms = false;

    public ?string $ticket = null;

    public function mount(?string $ticket = null): void
    {
        $ticket = $ticket ?? request()->query('ticket');

        if ($ticket && Str::isUuid($ticket)) {
            $this->ticket = $ticket;
        }
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate(UserRules::registrationRules());

        $validated['password'] = Hash::make($validated['password']);

        $user = app(RegisterUser::class)->handle($validated);

        Auth::login($user);

        $this->attachCliTicket($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    protected function attachCliTicket(User $user): void
    {
        if (! $this->ticket) {
            return;
        }

        $session = CliLoginSession::query()->find($this->ticket);

        if (! $session || $session->isExpired() || $session->status !== CliLoginSessionStatus::Pending) {
            return;
        }

        $session->forceFill([
            'user_id' => $user->id,
            'status' => CliLoginSessionStatus::VerificationRequired,
            'approved_at' => null,
        ])->save();
    }

    public function render()
    {
        return view('account.register')
            ->layout('components.layouts.auth', [
                'title' => 'Sign up',
                'canonical' => route('register'),
            ]);
    }
}
