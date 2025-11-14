<?php

namespace App\Environment\Actions\Token;

use App\Environment\Models\DeploymentToken;
use Laravel\Sanctum\NewAccessToken;

final class DeploymentTokenResult
{
    public function __construct(
        public readonly DeploymentToken $token,
        public readonly string $plainTextSecret,
        public readonly NewAccessToken $accessToken
    ) {}
}
