<?php

namespace App\Environment\Livewire;

use App\Auth\Models\PersonalAccessToken;
use App\Environment\Actions\Token\DeleteEnvToken;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class EnvironmentAccessTokenManager extends EnvironmentComponent
{
    /**
     * Get all CLI Tokens scoped to this environment.
     *
     * @return Collection<int, PersonalAccessToken>
     */
    #[Computed]
    public function tokens(): Collection
    {
        return $this->environment->tokens;
    }

    /**
     * Remove the given CLI token for the current environment.
     */
    public function remove(PersonalAccessToken $token): void
    {
        $this->authorize('manageTokens', $this->environment);

        app(DeleteEnvToken::class)->handle(
            token: $token,
            user: Auth::user()
        );

        Flux::toast(text: "Token '{$token->name}' removed.", variant: 'success');
    }

    public function render()
    {
        return view('environment.environment-access-token-manager');
    }
}
