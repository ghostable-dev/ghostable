<?php

declare(strict_types=1);

namespace App\Api\V2\Environment\Presenters;

use App\Environment\Models\EnvironmentVariablePromotionRequest;

final class EnvironmentVariablePromotionRequestPresenter
{
    public function present(EnvironmentVariablePromotionRequest $request): array
    {
        $sourceEnvironment = $request->sourceEnvironment;
        $targetEnvironment = $request->targetEnvironment;
        $requestedBy = $request->requestedByUser;
        $resolvedBy = $request->resolvedByUser;
        $entries = is_array($request->entries) ? $request->entries : [];

        return [
            'data' => [
                'type' => 'environment-variable-promotion-requests',
                'id' => (string) $request->getKey(),
                'attributes' => [
                    'organization_id' => (string) $request->organization_id,
                    'project_id' => (string) $request->project_id,
                    'source_environment_id' => (string) $request->source_environment_id,
                    'source_environment_name' => $sourceEnvironment?->name,
                    'target_environment_id' => (string) $request->target_environment_id,
                    'target_environment_name' => $targetEnvironment?->name,
                    'status' => $request->status?->value,
                    'include_values' => (bool) $request->include_values,
                    'target_key_version' => $request->target_key_version,
                    'entry_count' => count($entries),
                    'entries' => collect($entries)
                        ->map(function ($entry) {
                            $entryData = is_array($entry) ? $entry : [];

                            return [
                                'name' => (string) ($entryData['name'] ?? ''),
                                'source_if_version' => isset($entryData['source_if_version']) ? (int) $entryData['source_if_version'] : null,
                                'line_bytes' => isset($entryData['line_bytes']) ? (int) $entryData['line_bytes'] : null,
                                'is_commented' => array_key_exists('is_commented', $entryData) ? (bool) $entryData['is_commented'] : null,
                                'source_value_present' => array_key_exists('source_value_present', $entryData) ? (bool) $entryData['source_value_present'] : null,
                                'has_payload' => is_array($entryData['payload'] ?? null),
                                'payload' => $entryData['payload'] ?? null,
                                'payload_signing_json' => is_string($entryData['payload_signing_json'] ?? null)
                                    ? (string) $entryData['payload_signing_json']
                                    : null,
                            ];
                        })
                        ->values()
                        ->all(),
                    'created_at' => $request->created_at?->toIso8601String(),
                    'resolved_at' => $request->resolved_at?->toIso8601String(),
                    'rejected_reason' => $request->rejected_reason,
                    'cancel_reason' => $request->cancel_reason,
                ],
                'relationships' => [
                    'requested_by_user' => [
                        'data' => $requestedBy ? [
                            'type' => 'users',
                            'id' => (string) $requestedBy->getKey(),
                            'attributes' => [
                                'name' => $requestedBy->name,
                                'email' => $requestedBy->email,
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
     * @param  iterable<EnvironmentVariablePromotionRequest>  $requests
     */
    public function presentMany(iterable $requests): array
    {
        return [
            'data' => collect($requests)
                ->map(fn (EnvironmentVariablePromotionRequest $request) => $this->present($request)['data'])
                ->values()
                ->all(),
        ];
    }
}
