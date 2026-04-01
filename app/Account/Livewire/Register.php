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
    public bool $embedded = false;

    public bool $showHeading = true;

    public bool $showLoginLink = true;

    public bool $showNameField = false;

    public string $submitLabel = 'Create account';

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
        $this->fillMissingNameFromEmail();

        $validated = $this->validate(UserRules::registrationRules());

        if (! filled($validated['name'] ?? null)) {
            $validated['name'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);

        $user = app(RegisterUser::class)->handle($validated);

        Auth::login($user);

        $this->attachCliTicket($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    protected function fillMissingNameFromEmail(): void
    {
        if (filled(trim($this->name))) {
            return;
        }

        $inferredName = $this->inferNameFromEmail($this->email);

        if ($inferredName !== null) {
            $this->name = $inferredName;
        }
    }

    protected function inferNameFromEmail(string $email): ?string
    {
        $localPart = (string) Str::of(strtolower(trim($email)))
            ->before('@')
            ->before('+');

        if ($localPart === '') {
            return null;
        }

        if (in_array($localPart, [
            'admin',
            'billing',
            'contact',
            'hello',
            'hi',
            'info',
            'mail',
            'marketing',
            'no-reply',
            'noreply',
            'ops',
            'sales',
            'support',
            'team',
        ], true)) {
            return null;
        }

        $parts = preg_split('/[._-]+/', $localPart) ?: [];

        $parts = array_values(array_filter(array_map(static function (string $part): string {
            $trimmedPart = preg_replace('/^\d+|\d+$/', '', $part);

            if (! is_string($trimmedPart) || ! preg_match('/[a-z]/i', $trimmedPart)) {
                return '';
            }

            return $trimmedPart;
        }, $parts)));

        if (count($parts) >= 2) {
            return collect(array_slice($parts, 0, 2))
                ->map(fn (string $part): string => Str::headline($part))
                ->implode(' ');
        }

        if (count($parts) === 1) {
            $singlePart = preg_replace('/\d+/', '', $parts[0]);

            if (! is_string($singlePart) || $singlePart === '' || strlen($singlePart) < 2) {
                return null;
            }

            return Str::headline($singlePart);
        }

        return null;
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
