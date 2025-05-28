<?php

namespace App\Account\Livewire;

use App\Account\Actions\RegisterUser;
use App\Account\Rules\UserRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $terms = false;

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate(UserRules::registrationRules());

        $validated['password'] = Hash::make($validated['password']);

        $user = app(RegisterUser::class)->handle($validated);

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('account.register');
    }
}
