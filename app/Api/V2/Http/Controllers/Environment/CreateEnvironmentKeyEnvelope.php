<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentKeyPresenter;
use App\Api\V2\Environment\Requests\StoreEnvironmentKeyEnvelopeRequest;
use App\Api\V2\Http\Controllers\Concerns\LogsEnvironmentKeyActivity;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use App\Environment\Actions\StoreEnvironmentKeyEnvelope;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use JsonException;

final class CreateEnvironmentKeyEnvelope extends Controller
{
    use LogsEnvironmentKeyActivity;

    public function __invoke(
        StoreEnvironmentKeyEnvelopeRequest $request,
        Project $project,
        string $name,
        StoreEnvironmentKeyEnvelope $storeEnvironmentKeyEnvelope,
        ManageEnvironmentKeyReshareRequests $manageEnvironmentKeyReshareRequests,
        EnvironmentKeyPresenter $presenter,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        VerifyClientPayloadSignature $verifyClientPayloadSignature
    ): JsonResponse {
        $environment = $project->environmentOrFail($name);

        $this->authorize('manageSettings', $environment);

        $data = $request->validated();

        $organization = $environment->owningOrganization();

        $signingDevice = $this->resolveOrganizationDevice(
            deviceId: (string) $data['device_id'],
            organization: $organization,
            attribute: 'device_id'
        );

        $ensureDeviceOwnership->handle($signingDevice, $request->user());

        $payloadToVerify = $this->signaturePayloadFromValidatedData($data);

        $this->verifyWithFallback(
            request: $request,
            primaryPayload: $payloadToVerify,
            signatureBase64: $data['client_sig'],
            device: $signingDevice,
            verifyClientPayloadSignature: $verifyClientPayloadSignature
        );

        unset($data['client_sig']);

        /** @var EnvironmentKey|null $environmentKey */
        $environmentKey = $environment->keys()
            ->where('fingerprint', $data['fingerprint'])
            ->first();

        if (! $environmentKey) {
            throw ValidationException::withMessages([
                'fingerprint' => 'The selected environment key is invalid.',
            ]);
        }

        if (! $this->environmentKeyHasDeviceRecipient($environmentKey, (string) $signingDevice->getKey())) {
            throw ValidationException::withMessages([
                'device_id' => 'This device cannot fulfill key re-share requests for the selected environment key.',
            ]);
        }

        $previousRecipientIds = $this->recipientIdsByType(
            is_array($environmentKey->envelope?->recipients)
                ? $environmentKey->envelope->recipients
                : null
        );

        $envelopePayload = $data['envelope'];

        $recipients = data_get($envelopePayload, 'recipients', []);

        if (is_array($recipients)) {
            foreach ($recipients as $index => $recipient) {
                $type = $this->normalizeRecipientType($recipient['type'] ?? null);

                if ($type === null) {
                    continue;
                }

                $attribute = sprintf('envelope.recipients.%d.id', $index);

                if ($type === 'device') {
                    $device = $this->resolveOrganizationDevice(
                        deviceId: (string) $recipient['id'],
                        organization: $organization,
                        attribute: $attribute
                    );

                    $recipients[$index]['id'] = (string) $device->getKey();
                    $recipients[$index]['type'] = 'device';

                    continue;
                }

                if ($type === 'deployment') {
                    $token = $this->resolveDeploymentToken(
                        (string) $recipient['id'],
                        $environment,
                        $attribute
                    );

                    $recipients[$index]['id'] = (string) $token->getKey();
                    $recipients[$index]['type'] = 'deployment';
                }
            }
        }

        $storeEnvironmentKeyEnvelope->handle(
            $environmentKey,
            [
                'ciphertext_b64' => (string) $envelopePayload['ciphertext_b64'],
                'nonce_b64' => (string) $envelopePayload['nonce_b64'],
                'alg' => $envelopePayload['alg'] ?? null,
                'version' => $envelopePayload['version'] ?? null,
                'aad_b64' => $envelopePayload['aad_b64'] ?? null,
                'recipients' => $recipients ?: null,
            ]
        );

        /** @var User $user */
        $user = $request->user();

        $updatedRecipientIds = $this->recipientIdsByType($recipients);
        $recipientDiff = $this->buildRecipientDiff(
            before: $previousRecipientIds,
            after: $updatedRecipientIds,
            environment: $environment,
        );

        $manageEnvironmentKeyReshareRequests->completeForEnvelopeRecipients(
            environment: $environment,
            environmentKey: $environmentKey,
            recipients: $recipients,
            requestIds: collect($data['request_ids'] ?? [])
                ->map(fn (mixed $requestId): string => (string) $requestId)
                ->filter()
                ->values()
                ->all(),
            actor: $user,
            actorDevice: $signingDevice,
            request: $request,
            triggerSource: 'manual',
        );

        $environmentKey->load('envelope');

        Log::info('Environment key re-share recipients updated.', [
            'environment_key_id' => (string) $environmentKey->getKey(),
            'environment_id' => (string) $environment->getKey(),
            'project_id' => (string) $project->getKey(),
            'organization_id' => (string) $organization->getKey(),
            'actor_user_id' => (string) $user->getKey(),
            'actor_device_id' => (string) $signingDevice->getKey(),
            'recipient_diff' => $recipientDiff,
        ]);

        $this->logEnvironmentKeyActivity(
            event: 'environment_key_reshared',
            message: "Re-shared environment key v{$environmentKey->version} for \"{$environment->name}\".",
            environmentKey: $environmentKey,
            project: $project,
            environment: $environment,
            user: $user,
            request: $request,
            context: [
                'recipient_counts' => [
                    'device' => $this->countRecipientsOfType($recipients, 'device'),
                    'deployment' => $this->countRecipientsOfType($recipients, 'deployment'),
                ],
                'recipient_diff' => $recipientDiff,
            ],
        );

        return response()->json(
            $presenter->present($environmentKey)
        );
    }

    private function resolveOrganizationDevice(string $deviceId, Organization $organization, string $attribute): Device
    {
        /** @var Device $device */
        $device = Device::query()->with('user.organizations')->findOrFail($deviceId);

        if (! $device->user || ! $device->user->organizationMembership()->belongsToOrganization($organization)) {
            throw ValidationException::withMessages([
                $attribute => 'The selected device does not belong to the organization.',
            ]);
        }

        if ($device->isRevoked()) {
            throw ValidationException::withMessages([
                $attribute => 'The selected device is revoked.',
            ]);
        }

        return $device;
    }

    private function resolveDeploymentToken(string $deploymentTokenId, Environment $environment, string $attribute): DeploymentToken
    {
        /** @var DeploymentToken|null $token */
        $token = $environment->deploymentTokens()->find($deploymentTokenId);

        if (! $token) {
            throw ValidationException::withMessages([
                $attribute => 'The selected deployment token does not belong to the environment.',
            ]);
        }

        if ($token->isRevoked()) {
            throw ValidationException::withMessages([
                $attribute => 'The selected deployment token is revoked.',
            ]);
        }

        return $token;
    }

    private function normalizeRecipientType(mixed $type): ?string
    {
        if (! is_string($type)) {
            return null;
        }

        return match (strtolower($type)) {
            'deployment',
            'deployment-token',
            'deployment_tokens',
            'deploymenttoken',
            'deploymenttokens' => 'deployment',
            default => $type,
        };
    }

    private function environmentKeyHasDeviceRecipient(EnvironmentKey $environmentKey, string $deviceId): bool
    {
        $recipients = $environmentKey->envelope?->recipients;

        if (! is_array($recipients) || $recipients === []) {
            return false;
        }

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $type = strtolower((string) ($recipient['type'] ?? ''));
            $recipientId = (string) ($recipient['id'] ?? '');

            if ($type === 'device' && $recipientId === $deviceId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the exact signed payload shape expected by clients:
     * device_id, fingerprint, envelope.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function signaturePayloadFromValidatedData(array $data): array
    {
        $envelope = is_array($data['envelope'] ?? null) ? $data['envelope'] : [];

        $payloadEnvelope = [
            'ciphertext_b64' => (string) ($envelope['ciphertext_b64'] ?? ''),
            'nonce_b64' => (string) ($envelope['nonce_b64'] ?? ''),
        ];

        if (array_key_exists('alg', $envelope)) {
            $payloadEnvelope['alg'] = $envelope['alg'];
        }

        if (array_key_exists('recipients', $envelope)) {
            $payloadEnvelope['recipients'] = is_array($envelope['recipients']) ? array_values($envelope['recipients']) : $envelope['recipients'];
        }

        return [
            'device_id' => (string) $data['device_id'],
            'fingerprint' => (string) $data['fingerprint'],
            'envelope' => $payloadEnvelope,
            ...(array_key_exists('request_ids', $data)
                ? ['request_ids' => is_array($data['request_ids']) ? array_values($data['request_ids']) : $data['request_ids']]
                : []),
        ];
    }

    /**
     * @param  array<string, mixed>  $primaryPayload
     */
    private function verifyWithFallback(
        Request $request,
        array $primaryPayload,
        string $signatureBase64,
        Device $device,
        VerifyClientPayloadSignature $verifyClientPayloadSignature
    ): void {
        try {
            $verifyClientPayloadSignature->handle(
                payload: $primaryPayload,
                signatureBase64: $signatureBase64,
                device: $device,
                attributePath: 'client_sig',
                contextLabel: 'environment key envelope'
            );

            return;
        } catch (ValidationException $primaryException) {
            $signedPayload = $this->signaturePayloadFromSignedRawRequest($request, $signatureBase64);

            if ($signedPayload !== null) {
                try {
                    $verifyClientPayloadSignature->handleRawPayload(
                        payloadJson: $signedPayload,
                        signatureBase64: $signatureBase64,
                        device: $device,
                        attributePath: 'client_sig',
                        contextLabel: 'environment key envelope'
                    );

                    return;
                } catch (ValidationException) {
                    // keep trying alternate payload shapes
                }
            }

            $fallbackPayloads = [
                $request->all(),
                $this->signaturePayloadFromRawJson($request),
            ];

            foreach ($fallbackPayloads as $fallbackPayload) {
                if (! is_array($fallbackPayload)) {
                    continue;
                }

                unset($fallbackPayload['client_sig']);

                if ($fallbackPayload === $primaryPayload) {
                    continue;
                }

                try {
                    $verifyClientPayloadSignature->handle(
                        payload: $fallbackPayload,
                        signatureBase64: $signatureBase64,
                        device: $device,
                        attributePath: 'client_sig',
                        contextLabel: 'environment key envelope'
                    );

                    return;
                } catch (ValidationException) {
                    // keep trying alternate payload shapes
                }
            }

            throw $primaryException;
        }
    }

    private function signaturePayloadFromSignedRawRequest(
        Request $request,
        string $signatureBase64
    ): ?string {
        $content = trim($request->getContent());

        if ($content === '' || $signatureBase64 === '') {
            return null;
        }

        $escapedSig = preg_quote($signatureBase64, '/');

        $withoutClientSig = preg_replace(
            '/^\{\s*"client_sig"\s*:\s*"'.$escapedSig.'"\s*,\s*/',
            '{',
            $content,
            1,
            $count
        );

        if ($count === 1) {
            return $withoutClientSig;
        }

        $withoutClientSig = preg_replace(
            '/,\s*"client_sig"\s*:\s*"'.$escapedSig.'"\s*(?=[}\]])/',
            '',
            $content,
            1,
            $count
        );

        if ($count === 1) {
            return $withoutClientSig;
        }

        return null;
    }

    private function signaturePayloadFromRawJson(Request $request): ?array
    {
        $content = $request->getContent();

        if ($content === '') {
            return null;
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $recipients
     */
    private function countRecipientsOfType(?array $recipients, string $type): int
    {
        if (! $recipients) {
            return 0;
        }

        return collect($recipients)
            ->filter(fn (array $recipient): bool => (($recipient['type'] ?? null) === $type))
            ->count();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $recipients
     * @return array{device: array<int, string>, deployment: array<int, string>}
     */
    private function recipientIdsByType(?array $recipients): array
    {
        if (! is_array($recipients)) {
            return [
                'device' => [],
                'deployment' => [],
            ];
        }

        $deviceIds = [];
        $deploymentIds = [];

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $type = strtolower((string) ($recipient['type'] ?? ''));
            $id = (string) ($recipient['id'] ?? '');

            if ($id === '') {
                continue;
            }

            if ($type === 'device') {
                $deviceIds[] = $id;

                continue;
            }

            if ($type === 'deployment') {
                $deploymentIds[] = $id;
            }
        }

        return [
            'device' => array_values(array_unique($deviceIds)),
            'deployment' => array_values(array_unique($deploymentIds)),
        ];
    }

    /**
     * @param  array{device: array<int, string>, deployment: array<int, string>}  $before
     * @param  array{device: array<int, string>, deployment: array<int, string>}  $after
     * @return array<string, mixed>
     */
    private function buildRecipientDiff(array $before, array $after, Environment $environment): array
    {
        $deviceAddedIds = array_values(array_diff($after['device'], $before['device']));
        $deviceRemovedIds = array_values(array_diff($before['device'], $after['device']));
        $deviceUnchangedIds = array_values(array_intersect($before['device'], $after['device']));

        $deploymentAddedIds = array_values(array_diff($after['deployment'], $before['deployment']));
        $deploymentRemovedIds = array_values(array_diff($before['deployment'], $after['deployment']));
        $deploymentUnchangedIds = array_values(array_intersect($before['deployment'], $after['deployment']));

        return [
            'device' => [
                'before_count' => count($before['device']),
                'after_count' => count($after['device']),
                'unchanged_count' => count($deviceUnchangedIds),
                'added' => $this->describeDevices($deviceAddedIds),
                'removed' => $this->describeDevices($deviceRemovedIds),
            ],
            'deployment' => [
                'before_count' => count($before['deployment']),
                'after_count' => count($after['deployment']),
                'unchanged_count' => count($deploymentUnchangedIds),
                'added' => $this->describeDeploymentTokens($deploymentAddedIds, $environment),
                'removed' => $this->describeDeploymentTokens($deploymentRemovedIds, $environment),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $deviceIds
     * @return array<int, array<string, string|null>>
     */
    private function describeDevices(array $deviceIds): array
    {
        if ($deviceIds === []) {
            return [];
        }

        $devices = Device::query()
            ->with('user')
            ->whereIn('id', $deviceIds)
            ->get()
            ->keyBy(fn (Device $device): string => (string) $device->getKey());

        return collect($deviceIds)
            ->map(function (string $deviceId) use ($devices): array {
                /** @var Device|null $device */
                $device = $devices->get($deviceId);

                if (! $device) {
                    return [
                        'id' => $deviceId,
                        'name' => null,
                        'platform' => null,
                        'status' => 'missing',
                        'user_id' => null,
                    ];
                }

                return [
                    'id' => (string) $device->getKey(),
                    'name' => $device->name,
                    'platform' => $device->platform?->value,
                    'status' => $device->isRevoked() ? 'revoked' : 'active',
                    'user_id' => $device->user ? (string) $device->user->getKey() : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $deploymentTokenIds
     * @return array<int, array<string, string|null>>
     */
    private function describeDeploymentTokens(array $deploymentTokenIds, Environment $environment): array
    {
        if ($deploymentTokenIds === []) {
            return [];
        }

        $tokens = $environment->deploymentTokens()
            ->whereIn('id', $deploymentTokenIds)
            ->get()
            ->keyBy(fn (DeploymentToken $token): string => (string) $token->getKey());

        return collect($deploymentTokenIds)
            ->map(function (string $deploymentTokenId) use ($tokens): array {
                /** @var DeploymentToken|null $token */
                $token = $tokens->get($deploymentTokenId);

                if (! $token) {
                    return [
                        'id' => $deploymentTokenId,
                        'name' => null,
                        'status' => 'missing',
                    ];
                }

                return [
                    'id' => (string) $token->getKey(),
                    'name' => $token->name,
                    'status' => $token->isRevoked() ? 'revoked' : 'active',
                ];
            })
            ->values()
            ->all();
    }
}
