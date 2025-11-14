<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Environment\Presenters\EnvironmentKeyPresenter;
use App\Api\V2\Environment\Requests\StoreEnvironmentKeyEnvelopeRequest;
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
use Illuminate\Validation\ValidationException;

final class CreateEnvironmentKeyEnvelope extends Controller
{
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

        $payloadToVerify = $data;
        unset($payloadToVerify['client_sig']);

        $verifyClientPayloadSignature->handle(
            payload: $payloadToVerify,
            signatureBase64: $data['client_sig'],
            device: $signingDevice,
            attributePath: 'client_sig',
            contextLabel: 'environment key envelope'
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
}
