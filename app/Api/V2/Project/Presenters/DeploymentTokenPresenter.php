<?php

declare(strict_types=1);

namespace App\Api\V2\Project\Presenters;

use App\Environment\Models\DeploymentToken;
use Illuminate\Support\Collection;

final class DeploymentTokenPresenter
{
    /**
     * @param  list<string>|null  $only
     */
    public function present(DeploymentToken $token, ?array $only = null): array
    {
        $attributes = [
            'name' => $token->name,
            'environment_id' => (string) $token->environment_id,
            'project_id' => (string) $token->project_id,
            'public_key' => $token->public_key,
            'status' => $token->isRevoked() ? 'revoked' : 'active',
            'token_suffix' => $token->token_suffix,
            'created_at' => $token->created_at?->toIso8601String(),
            'updated_at' => $token->updated_at?->toIso8601String(),
            'revoked_at' => $token->revoked_at?->toIso8601String(),
        ];

        if ($only !== null) {
            $attributes = array_intersect_key($attributes, array_flip($only));
        }

        return [
            'data' => [
                'type' => 'deployment',
                'id' => (string) $token->getKey(),
                'attributes' => $attributes,
            ],
        ];
    }

    /**
     * @param  iterable<DeploymentToken>|Collection<int, DeploymentToken>  $tokens
     * @param  list<string>|null  $only
     */
    public function presentCollection(iterable $tokens, ?array $only = null): array
    {
        $collection = $tokens instanceof Collection ? $tokens : collect($tokens);

        return [
            'data' => $collection
                ->map(fn (DeploymentToken $token) => $this->present($token, $only)['data'])
                ->values()
                ->all(),
        ];
    }
}
