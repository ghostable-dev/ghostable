<?php

namespace App\Environment\Actions\Token;

use App\Account\Models\User;
use App\Environment\Models\DeploymentToken;

class RevokeDeploymentToken
{
    public function __construct(
        private readonly DeleteEnvToken $deleteEnvToken,
    ) {}

    public function handle(DeploymentToken $deploymentToken, ?User $user = null): DeploymentToken
    {
        if ($deploymentToken->personalAccessToken) {
            $this->deleteEnvToken->handle($deploymentToken->personalAccessToken, $user);
            $deploymentToken->unsetRelation('personalAccessToken');
        }

        $deploymentToken->markRevoked();

        return $deploymentToken->fresh();
    }
}
