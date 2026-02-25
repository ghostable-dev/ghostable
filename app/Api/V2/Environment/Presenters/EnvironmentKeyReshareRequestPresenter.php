<?php

declare(strict_types=1);

namespace App\Api\V2\Environment\Presenters;

use App\Environment\Models\EnvironmentKeyReshareRequest;

final class EnvironmentKeyReshareRequestPresenter
{
    public function present(EnvironmentKeyReshareRequest $request): array
    {
        $environment = $request->environment;
        $project = $request->project;
        $targetUser = $request->targetUser;
        $targetDevice = $request->targetDevice;
        $resolvedBy = $request->resolvedByUser;

        return [
            'data' => [
                'type' => 'environment-key-reshare-requests',
                'id' => (string) $request->getKey(),
                'attributes' => [
                    'organization_id' => (string) $request->organization_id,
                    'project_id' => (string) $request->project_id,
                    'environment_id' => (string) $request->environment_id,
                    'required_key_version' => (int) $request->required_key_version,
                    'target_user_id' => (string) $request->target_user_id,
                    'target_device_id' => (string) $request->target_device_id,
                    'status' => $request->status?->value,
                    'trigger_source' => $request->trigger_source,
                    'cancel_reason' => $request->cancel_reason,
                    'created_at' => $request->created_at?->toIso8601String(),
                    'resolved_at' => $request->resolved_at?->toIso8601String(),
                    'last_notified_at' => $request->last_notified_at?->toIso8601String(),
                ],
                'relationships' => [
                    'project' => [
                        'data' => $project ? [
                            'type' => 'projects',
                            'id' => (string) $project->getKey(),
                            'attributes' => [
                                'name' => $project->name,
                            ],
                        ] : null,
                    ],
                    'environment' => [
                        'data' => $environment ? [
                            'type' => 'environments',
                            'id' => (string) $environment->getKey(),
                            'attributes' => [
                                'name' => $environment->name,
                            ],
                        ] : null,
                    ],
                    'target_user' => [
                        'data' => $targetUser ? [
                            'type' => 'users',
                            'id' => (string) $targetUser->getKey(),
                            'attributes' => [
                                'name' => $targetUser->name,
                                'email' => $targetUser->email,
                            ],
                        ] : null,
                    ],
                    'target_device' => [
                        'data' => $targetDevice ? [
                            'type' => 'devices',
                            'id' => (string) $targetDevice->getKey(),
                            'attributes' => [
                                'name' => $targetDevice->name,
                                'platform' => $targetDevice->platform?->value,
                                'status' => $targetDevice->isRevoked() ? 'revoked' : 'active',
                            ],
                        ] : null,
                    ],
                    'resolved_by_user' => [
                        'data' => $resolvedBy ? [
                            'type' => 'users',
                            'id' => (string) $resolvedBy->getKey(),
                            'attributes' => [
                                'name' => $resolvedBy->name,
                                'email' => $resolvedBy->email,
                            ],
                        ] : null,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  iterable<EnvironmentKeyReshareRequest>  $requests
     */
    public function presentMany(iterable $requests): array
    {
        return [
            'data' => collect($requests)
                ->map(fn (EnvironmentKeyReshareRequest $request) => $this->present($request)['data'])
                ->values()
                ->all(),
        ];
    }
}
