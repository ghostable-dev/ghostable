<?php

namespace App\Environment\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Auth\Models\PersonalAccessToken;
use App\Environment\Actions\Token\CreateEnvToken;
use App\Environment\Actions\Token\DeleteEnvToken;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class EnvironmentAccessTokenManager extends EnvironmentComponent
{
    use ConfirmsPasswords;

    /**
     * The user-provided name for a new CLI token.
     */
    public string $name = '';

    /**
     * The user-provided name for a new CLI token.
     */
    public int $expires_after = 30;

    /**
     * Temporarily holds the newly-created token’s plain-text value.
     * Only populated immediately after creation so it can be shown once.
     */
    public ?array $newToken = [];

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
     * Create a new CLI token using the name provided
     * for the given environment.
     */
    public function create(): void
    {
        $this->authorize('manageTokens', $this->environment);

        $validated = $this->validate([
            'name' => 'required|max:255',
            'expires_after' => 'required|numeric|min:7,max:90',
        ]);

        $new = app(CreateEnvToken::class)->handle(
            name: $validated['name'],
            environment: $this->environment,
            expiresAfter: $validated['expires_after'],
            user: Auth::user()
        );

        $this->newToken['token'] = $new->plainTextToken;
        $this->newToken['expires'] = $new->accessToken->expires_at->timezone(timezone())->format(DT_FORMAT);

        $this->reset('name', 'expires_after');

        Flux::modal('create-token')->close();

        Flux::modal('show-token')->show();
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
