<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Environment\Requests\StoreEnvironmentBackupRequest;
use App\Backup\Actions\BuildEnvironmentBackup;
use App\Backup\Actions\LogEnvironmentBackupCreated;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\VerifyClientPayloadSignature;
use App\Crypto\Models\Device;
use App\Environment\Actions\BuildEncryptedProjection;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class CreateEnvironmentBackup extends Controller
{
    public function __invoke(
        StoreEnvironmentBackupRequest $request,
        Project $project,
        string $name,
        BuildEncryptedProjection $buildEncryptedProjection,
        BuildEnvironmentBackup $buildEnvironmentBackup,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        VerifyClientPayloadSignature $verifyClientPayloadSignature,
        LogEnvironmentBackupCreated $logEnvironmentBackupCreated
    ): JsonResponse {
        $environment = $project->environmentOrFail($name);

        $this->authorize('manageSettings', $environment);

        $data = $request->validated();
        $rawPayload = $request->all();

        $device = $this->resolveDevice((string) $data['device_id'], 'device_id');
        $ensureDeviceOwnership->handle($device, $request->user());

        $payloadToVerify = is_array($rawPayload) ? $rawPayload : [];
        unset($payloadToVerify['client_sig']);

        $verifyClientPayloadSignature->handle(
            payload: $payloadToVerify,
            signatureBase64: $data['client_sig'],
            device: $device,
            attributePath: 'client_sig',
            contextLabel: 'environment backup request'
        );

        unset($data['client_sig']);

        $recoveryPublicKey = $data['recovery_public_key'] ?? null;

        if ($recoveryPublicKey !== null) {
            $decodedRecoveryKey = base64_decode((string) $recoveryPublicKey, true);

            if ($decodedRecoveryKey === false || strlen($decodedRecoveryKey) !== SODIUM_CRYPTO_KX_PUBLICKEYBYTES) {
                throw ValidationException::withMessages([
                    'recovery_public_key' => ['Invalid recovery public key. Expected a base64-encoded X25519 key.'],
                ]);
            }
        }

        $bundle = $buildEncryptedProjection->handle(
            environment: $environment,
            includeMeta: true,
            includeVersions: true
        );

        $envelope = $buildEnvironmentBackup->handle(
            project: $project,
            environment: $environment,
            requestingDevice: $device,
            bundle: $bundle,
            recoveryPublicKey: $recoveryPublicKey,
            recoveryLabel: $data['recovery_label'] ?? null,
            requestIp: $request->ip()
        );

        $logEnvironmentBackupCreated->handle(
            environment: $environment,
            user: $request->user(),
            device: $device,
            envelope: $envelope
        );

        return response()->json($envelope, 201);
    }

    private function resolveDevice(string $deviceId, string $attribute): Device
    {
        /** @var Device|null $device */
        $device = Device::query()->find($deviceId);

        if (! $device) {
            throw ValidationException::withMessages([
                $attribute => ['The selected device is invalid.'],
            ]);
        }

        if ($device->isRevoked()) {
            throw ValidationException::withMessages([
                $attribute => ['The selected device is revoked.'],
            ]);
        }

        if (! is_string($device->public_key) || $device->public_key === '') {
            throw new RuntimeException('Device public encryption key is missing.');
        }

        return $device;
    }
}
