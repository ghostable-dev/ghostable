<?php

namespace App\Environment\Actions\Token;

use App\Account\Models\User;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;

class CreateDeploymentToken
{
    public function __construct(
        private readonly CreateEnvToken $createEnvToken,
        private readonly ShareEnvironmentKeyWithDeploymentToken $shareEnvironmentKeyWithDeploymentToken,
    ) {}

    public function handle(
        string $name,
        Environment $environment,
        string $publicKey,
        ?User $user = null,
        int $expiresAfter = 90,
        ?array $recipient = null,
    ): DeploymentTokenResult {
        $newToken = $this->createEnvToken->handle(
            name: $name,
            environment: $environment,
            expiresAfter: $expiresAfter,
            user: $user,
            abilities: ['deploy']
        );

        /** @var DeploymentToken $deploymentToken */
        $deploymentToken = DeploymentToken::query()->create([
            'environment_id' => $environment->getKey(),
            'project_id' => $environment->project_id,
            'personal_access_token_id' => $newToken->accessToken->getKey(),
            'name' => $name,
            'public_key' => $publicKey,
            'token_suffix' => $newToken->accessToken->token_suffix,
            'revoked_at' => null,
        ]);

        $deploymentToken->load('environment');

        $this->shareEnvironmentKeyWithDeploymentToken->handle($deploymentToken, $recipient);

        return new DeploymentTokenResult(
            token: $deploymentToken->fresh(),
            plainTextSecret: $newToken->plainTextToken,
            accessToken: $newToken,
        );
    }
}
