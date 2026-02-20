<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Environment\Presenters\EnvironmentKeyPresenter;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Models\Device;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class GetEnvironmentVariableHistoryValue extends Controller
{
    public function __invoke(
        Request $request,
        Project $project,
        string $name,
        string $variable,
        string $versionId,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        EnvironmentKeyPresenter $environmentKeyPresenter
    ): JsonResponse {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);

        $secret = EnvironmentSecret::query()
            ->where('environment_id', $environment->id)
            ->where('name', $variable)
            ->first();

        abort_unless($secret, 404, 'Variable not found in this environment.');

        /** @var EnvironmentSecretVersion|null $version */
        $version = $secret->versions()
            ->whereKey($versionId)
            ->first();

        if (! $version) {
            throw ValidationException::withMessages([
                'version_id' => 'The selected version does not belong to this variable.',
            ]);
        }

        $device = $this->resolveRequestDevice(
            request: $request,
            ensureDeviceOwnership: $ensureDeviceOwnership
        );

        $environmentKey = $this->resolveEnvironmentKeyForVersion(
            environment: $environment,
            version: $version
        );

        if (! $environmentKey || ! $environmentKey->envelope) {
            return response()->json([
                'message' => 'Unable to resolve environment key material for this version.',
            ], 409);
        }

        $presentedEnvironmentKey = data_get(
            $environmentKeyPresenter->present($environmentKey),
            'data'
        );

        if (! is_array($presentedEnvironmentKey)) {
            return response()->json([
                'message' => 'Environment key response is invalid.',
            ], 409);
        }

        $presentedEnvironmentKey = $this->filterPresentedEnvironmentKeyForDevice(
            presentedEnvironmentKey: $presentedEnvironmentKey,
            deviceId: (string) $device->getKey(),
        );

        $metadata = is_array($version->metadata) ? $version->metadata : [];
        $versionAad = is_array($version->aad) ? $version->aad : [];
        $normalizedAad = [
            'org' => (string) ($versionAad['org'] ?? $project->organization_id),
            'project' => (string) ($versionAad['project'] ?? $project->getKey()),
            'env' => (string) ($versionAad['env'] ?? $environment->getKey()),
            'name' => (string) ($versionAad['name'] ?? $secret->name),
        ];

        return response()->json([
            'data' => [
                'scope' => 'variable_version_value',
                'version_id' => (string) $version->getKey(),
                'variable' => $secret->name,
                'secret' => [
                    'env' => $environment->name,
                    'name' => $secret->name,
                    'ciphertext' => $version->ciphertext,
                    'nonce' => $version->nonce,
                    'alg' => $version->alg,
                    'aad' => $normalizedAad,
                    'claims' => $version->claims,
                    'env_kek_version' => $version->env_kek_version,
                    'env_kek_fingerprint' => $version->env_kek_fingerprint,
                    'version' => (int) $version->version,
                    'meta' => [
                        'line_bytes' => $version->line_bytes,
                        'is_vapor_secret' => (bool) data_get($metadata, 'laravel.is_vapor_secret', false),
                        'is_commented' => (bool) $version->is_commented,
                    ],
                ],
                'environment_key' => $presentedEnvironmentKey,
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function resolveRequestDevice(
        Request $request,
        EnsureDeviceOwnership $ensureDeviceOwnership
    ): Device {
        $deviceId = $request->query('device_id') ?? $request->header('X-Device-ID');

        if (! is_string($deviceId) || $deviceId === '') {
            throw ValidationException::withMessages([
                'device_id' => ['The device_id query parameter is required.'],
            ]);
        }

        /** @var Device|null $device */
        $device = Device::query()->find($deviceId);

        if (! $device) {
            throw ValidationException::withMessages([
                'device_id' => ['The selected device is invalid.'],
            ]);
        }

        if ($device->isRevoked()) {
            throw ValidationException::withMessages([
                'device_id' => ['The selected device is revoked.'],
            ]);
        }

        $user = $request->user();
        if (! $user) {
            throw ValidationException::withMessages([
                'device_id' => ['Unable to resolve requesting user for device ownership verification.'],
            ]);
        }

        $ensureDeviceOwnership->handle($device, $user);

        return $device;
    }

    private function resolveEnvironmentKeyForVersion(
        Environment $environment,
        EnvironmentSecretVersion $version
    ): ?EnvironmentKey {
        if ($version->env_kek_version !== null) {
            $byVersion = $environment->keys()
                ->with('envelope')
                ->where('version', (int) $version->env_kek_version)
                ->first();

            if ($byVersion) {
                return $byVersion;
            }
        }

        if (is_string($version->env_kek_fingerprint) && $version->env_kek_fingerprint !== '') {
            $byFingerprint = $environment->keys()
                ->with('envelope')
                ->where('fingerprint', $version->env_kek_fingerprint)
                ->first();

            if ($byFingerprint) {
                return $byFingerprint;
            }
        }

        return $environment->keys()
            ->with('envelope')
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Filter envelope recipients to the requesting device before returning key payload.
     *
     * @param  array<string, mixed>  $presentedEnvironmentKey
     * @return array<string, mixed>
     */
    private function filterPresentedEnvironmentKeyForDevice(array $presentedEnvironmentKey, string $deviceId): array
    {
        $recipients = data_get(
            $presentedEnvironmentKey,
            'relationships.envelope.data.attributes.recipients'
        );

        if (is_array($recipients)) {
            $filteredRecipients = array_values(array_filter(
                $recipients,
                static function ($recipient) use ($deviceId): bool {
                    if (! is_array($recipient)) {
                        return false;
                    }

                    $type = strtolower((string) ($recipient['type'] ?? ''));
                    $id = (string) ($recipient['id'] ?? '');

                    return $type === 'device' && $id === $deviceId;
                }
            ));

            data_set(
                $presentedEnvironmentKey,
                'relationships.envelope.data.attributes.recipients',
                $filteredRecipients
            );
        }

        $deviceEnvelopes = data_get($presentedEnvironmentKey, 'relationships.envelopes.data');
        if (is_array($deviceEnvelopes)) {
            $filteredDeviceEnvelopes = array_values(array_filter(
                $deviceEnvelopes,
                static function ($envelope) use ($deviceId): bool {
                    if (! is_array($envelope)) {
                        return false;
                    }

                    return (string) ($envelope['id'] ?? '') === $deviceId;
                }
            ));

            data_set($presentedEnvironmentKey, 'relationships.envelopes.data', $filteredDeviceEnvelopes);
        }

        return $presentedEnvironmentKey;
    }
}
