<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentKeyPresenter;
use App\Api\V2\Environment\Requests\StoreEnvironmentKeyRequest;
use App\Api\V2\Http\Controllers\Concerns\LogsEnvironmentKeyActivity;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Actions\CreateEnvironmentKey as CreateEnvironmentKeyAction;
use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use App\Environment\Actions\StoreEnvironmentKeyEnvelope;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use JsonException;

final class CreateEnvironmentKey extends Controller
{
    use LogsEnvironmentKeyActivity;

    public function __invoke(
        StoreEnvironmentKeyRequest $request,
        Project $project,
        string $name,
        CreateEnvironmentKeyAction $createEnvironmentKey,
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

        $createdByDevice = null;

        if (! empty($data['created_by_device_id'])) {
            $createdByDevice = $this->resolveOrganizationDevice(
                deviceId: (string) $data['created_by_device_id'],
                organization: $organization,
                attribute: 'created_by_device_id'
            );
        }

        $environmentKey = $createEnvironmentKey->handle(
            environment: $environment,
            fingerprint: $data['fingerprint'],
            createdByDevice: $createdByDevice,
            version: $data['version'] ?? null,
            rotatedAt: isset($data['rotated_at']) ? Carbon::parse($data['rotated_at']) : null,
        );

        [$envelopePayload, $recipients] = $this->resolveEnvelopePayload(
            data: $data,
            organization: $organization,
            project: $project,
            environment: $environment
        );

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

        $manageEnvironmentKeyReshareRequests->markSupersededForEnvironment(
            environment: $environment,
            activeKeyVersion: (int) $environmentKey->version,
            triggerSource: 'manual',
            actor: $user,
            request: $request,
        );

        $manageEnvironmentKeyReshareRequests->completeForEnvelopeRecipients(
            environment: $environment,
            environmentKey: $environmentKey,
            recipients: $recipients,
            actor: $user,
            actorDevice: $signingDevice,
            request: $request,
            triggerSource: 'manual',
        );

        $manageEnvironmentKeyReshareRequests->syncForEnvironment(
            environment: $environment,
            triggerSource: 'manual',
            actor: $user,
            request: $request,
            notifyActors: true,
        );

        $environmentKey->load('envelope');

        $this->logEnvironmentKeyActivity(
            event: 'environment_key_created',
            message: "Created environment key v{$environmentKey->version} for \"{$environment->name}\".",
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
            ],
        );

        return response()->json(
            $presenter->present($environmentKey),
            201
        );
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>|null}
     */
    private function resolveEnvelopePayload(
        array $data,
        Organization $organization,
        Project $project,
        Environment $environment
    ): array {
        if (isset($data['envelopes']) && is_array($data['envelopes'])) {
            return $this->prepareDeviceEnvelopePayload($data['envelopes'], $organization);
        }

        $envelopePayload = $data['envelope'] ?? [];

        $recipients = $this->normalizeRecipients(
            data_get($envelopePayload, 'recipients', []),
            $organization,
            $project,
            $environment,
            'envelope.recipients'
        );

        $envelopePayload['recipients'] = $recipients;

        return [$envelopePayload, $recipients];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function normalizeRecipients(
        mixed $recipients,
        Organization $organization,
        Project $project,
        Environment $environment,
        string $attributePrefix
    ): ?array {
        if (! is_array($recipients)) {
            return null;
        }

        $normalized = [];

        foreach ($recipients as $index => $recipient) {
            if (! is_array($recipient)) {
                $normalized[$index] = $recipient;

                continue;
            }

            $type = $this->normalizeRecipientType($recipient['type'] ?? null);

            if ($type === null) {
                $normalized[$index] = $recipient;

                continue;
            }

            $attribute = sprintf('%s.%d.id', $attributePrefix, $index);

            if ($type === 'device') {
                $device = $this->resolveOrganizationDevice(
                    deviceId: (string) ($recipient['id'] ?? ''),
                    organization: $organization,
                    attribute: $attribute
                );

                $recipient['id'] = (string) $device->getKey();
                $recipient['type'] = 'device';
            } elseif ($type === 'deployment') {
                $token = $this->resolveDeploymentToken(
                    deploymentTokenId: (string) ($recipient['id'] ?? ''),
                    project: $project,
                    environment: $environment,
                    attribute: $attribute
                );

                $recipient['id'] = (string) $token->getKey();
                $recipient['type'] = 'deployment';
            } else {
                $recipient['type'] = $type;
            }

            $normalized[$index] = $recipient;
        }

        return $normalized ?: null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $envelopes
     * @return array{0: array<string, mixed>, 1: array<int, array<string, string>>}
     */
    private function prepareDeviceEnvelopePayload(array $envelopes, Organization $organization): array
    {
        $recipients = [];
        $primaryAttributes = null;

        foreach ($envelopes as $index => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $attributeBase = sprintf('envelopes.%d', $index);

            $device = $this->resolveOrganizationDevice(
                deviceId: (string) ($payload['device_id'] ?? ''),
                organization: $organization,
                attribute: $attributeBase.'.device_id'
            );

            $recipients[] = [
                'type' => 'device',
                'id' => (string) $device->getKey(),
                'edek_b64' => $this->encodeEnvelopePayload($payload, $attributeBase),
            ];

            if ($primaryAttributes !== null) {
                continue;
            }

            $primaryAttributes = [
                'ciphertext_b64' => (string) $payload['ciphertext_b64'],
                'nonce_b64' => (string) $payload['nonce_b64'],
                'alg' => (string) ($payload['alg'] ?? 'xchacha20-poly1305'),
                'version' => (string) ($payload['version'] ?? '1'),
                'aad_b64' => $payload['aad_b64'] ?? null,
            ];
        }

        if ($primaryAttributes === null) {
            throw ValidationException::withMessages([
                'envelopes' => 'At least one envelope must be provided.',
            ]);
        }

        return [$primaryAttributes, $recipients];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodeEnvelopePayload(array $payload, string $attributeBase): string
    {
        $normalized = [
            'ciphertext_b64' => (string) $payload['ciphertext_b64'],
            'nonce_b64' => (string) $payload['nonce_b64'],
            'alg' => (string) ($payload['alg'] ?? 'xchacha20-poly1305'),
            'version' => (string) ($payload['version'] ?? '1'),
            'aad_b64' => $payload['aad_b64'] ?? null,
            'from_ephemeral_public_key' => $payload['from_ephemeral_public_key'] ?? null,
        ];

        if (! empty($payload['expires_at'])) {
            $normalized['expires_at'] = Carbon::parse($payload['expires_at'])->toIso8601String();
        }

        try {
            return 'b64:'.base64_encode(json_encode($normalized, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            throw ValidationException::withMessages([
                $attributeBase => 'Failed to encode envelope payload.',
            ]);
        }
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

    private function resolveDeploymentToken(
        string $deploymentTokenId,
        Project $project,
        Environment $environment,
        string $attribute
    ): DeploymentToken {
        /** @var DeploymentToken|null $token */
        $token = DeploymentToken::query()->find($deploymentTokenId);

        if (! $token || $token->project_id !== $project->getKey()) {
            throw ValidationException::withMessages([
                $attribute => 'The selected deployment token does not belong to the project.',
            ]);
        }

        if ($token->environment_id !== $environment->getKey()) {
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

    /**
     * @param  array<string, mixed>  $primaryPayload
     */
    private function verifyWithFallback(
        StoreEnvironmentKeyRequest $request,
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
                contextLabel: 'environment key'
            );

            return;
        } catch (ValidationException $primaryException) {
            $fallbackPayload = $request->all();
            if (! is_array($fallbackPayload)) {
                throw $primaryException;
            }

            unset($fallbackPayload['client_sig']);

            if ($fallbackPayload === $primaryPayload) {
                throw $primaryException;
            }

            try {
                $verifyClientPayloadSignature->handle(
                    payload: $fallbackPayload,
                    signatureBase64: $signatureBase64,
                    device: $device,
                    attributePath: 'client_sig',
                    contextLabel: 'environment key'
                );
            } catch (ValidationException) {
                throw $primaryException;
            }
        }
    }

    /**
     * Build the exact signed payload shape expected by clients:
     * device_id, fingerprint, optional version/created_by_device_id/rotated_at, envelope.
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

        $payload = [
            'device_id' => (string) $data['device_id'],
            'fingerprint' => (string) $data['fingerprint'],
        ];

        if (array_key_exists('version', $data)) {
            $payload['version'] = $data['version'];
        }

        if (array_key_exists('created_by_device_id', $data)) {
            $payload['created_by_device_id'] = $data['created_by_device_id'];
        }

        if (array_key_exists('rotated_at', $data)) {
            $payload['rotated_at'] = $data['rotated_at'];
        }

        $payload['envelope'] = $payloadEnvelope;

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
}
