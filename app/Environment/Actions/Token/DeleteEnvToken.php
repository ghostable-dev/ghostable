<?php

namespace App\Environment\Actions\Token;

use App\Account\Models\User;
use App\Auth\Models\PersonalAccessToken;

class DeleteEnvToken
{
    public function handle(
        PersonalAccessToken $token,
        ?User $user = null
    ): void {
        $token->delete();

        app(LogEnvTokenActivity::class)->handle(token: $token, event: 'deleted', user: $user);
    }
}
