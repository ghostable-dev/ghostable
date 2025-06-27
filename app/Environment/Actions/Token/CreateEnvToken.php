<?php

namespace App\Environment\Actions\Token;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use Laravel\Sanctum\NewAccessToken;

class CreateEnvToken
{
    public function handle(
        string $name, 
        Environment $environment, 
        int $expiresAfter = 90,
        ?User $user = null
    ): NewAccessToken
    {
        $expires = now()->addDays($expiresAfter);
        
        $plainTextToken = $environment->generateTokenString();

        $token = $environment->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'token_suffix' => str($plainTextToken)->substr(-8),
            'abilities' => ['*'],
            'expires_at' => $expires,
        ]);

        $new = new NewAccessToken($token, $token->id.'|'.$plainTextToken);
        
        app(LogEnvTokenActivity::class)->handle(token: $token, event: 'created', user: $user);
        
        return $new;
    }
}
