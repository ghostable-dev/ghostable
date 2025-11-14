<?php

declare(strict_types=1);

namespace App\Environment\Support;

use App\Environment\Models\DeploymentToken;

class DeploymentTokenAuditProperties
{
    /**
     * Build a consistent metadata payload for deployment token activity logs.
     *
     * @return array<string, mixed>
     */
    public static function make(DeploymentToken $token): array
    {
        $token->loadMissing([
            'environment.project.organization',
            'project.organization',
        ]);

        $environment = $token->environment;
        $project = $token->project ?? $environment?->project;

        return array_filter([
            'id' => (string) $token->getKey(),
            'name' => $token->name,
            'token_suffix' => $token->token_suffix,
            'status' => $token->isRevoked() ? 'revoked' : 'active',
            'public_key_present' => (bool) $token->public_key,
            'environment_id' => $environment?->id ? (string) $environment->id : null,
            'environment_name' => $environment?->name,
            'project_id' => $project?->id ? (string) $project->id : null,
            'project_name' => $project?->name,
            'revoked_at' => $token->revoked_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
            'updated_at' => $token->updated_at?->toIso8601String(),
        ], static fn ($value) => $value !== null);
    }
}
