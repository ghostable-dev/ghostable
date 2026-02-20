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
use App\Environment\Actions\StoreEnvironmentKeyEnvelope;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CreateEnvironmentKeyEnvelope extends Controller
{
    use LogsEnvironmentKeyActivity;

    public function __invoke(
        StoreEnvironmentKeyEnvelopeRequest $request,
        Project $project,
        string $name,
        StoreEnvironmentKeyEnvelope $storeEnvironmentKeyEnvelope,
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

        $environmentKey->load('envelope');

        /** @var User $user */
        $user = $request->user();

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
                    contextLabel: 'environment key envelope'
                );
            } catch (ValidationException) {
                throw $primaryException;
            }
        }
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
