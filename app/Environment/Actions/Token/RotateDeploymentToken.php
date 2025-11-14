<?php

namespace App\Environment\Actions\Token;

use App\Account\Models\User;
use App\Environment\Models\DeploymentToken;

class RotateDeploymentToken
{
    public function __construct(
        private readonly CreateEnvToken $createEnvToken,
        private readonly DeleteEnvToken $deleteEnvToken,
        private readonly ShareEnvironmentKeyWithDeploymentToken $shareEnvironmentKeyWithDeploymentToken,
    ) {}

    public function handle(
        DeploymentToken $deploymentToken,
        ?User $user = null,
        ?string $publicKey = null,
        int $expiresAfter = 90
    ): DeploymentTokenResult {
        if ($deploymentToken->personalAccessToken) {
            $this->deleteEnvToken->handle($deploymentToken->personalAccessToken, $user);
            $deploymentToken->unsetRelation('personalAccessToken');
        }

        $newToken = $this->createEnvToken->handle(
            name: $deploymentToken->name,
            environment: $deploymentToken->environment,
            expiresAfter: $expiresAfter,
            user: $user,
            abilities: ['deploy']
        );

        $updates = [
            'personal_access_token_id' => $newToken->accessToken->getKey(),
            'token_suffix' => $newToken->accessToken->token_suffix,
            'revoked_at' => null,
        ];

        if ($publicKey !== null) {
            $updates['public_key'] = $publicKey;
        }

        $deploymentToken->forceFill($updates)->save();

        $deploymentToken->refresh();
        $deploymentToken->load('environment');

        $this->shareEnvironmentKeyWithDeploymentToken->handle($deploymentToken);

        return new DeploymentTokenResult(
            token: $deploymentToken->fresh(),
            plainTextSecret: $newToken->plainTextToken,
            accessToken: $newToken,
        );
    }
}
