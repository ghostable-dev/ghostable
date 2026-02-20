<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Models\Device;
use App\Environment\Actions\BuildEncryptedProjection;
use App\Environment\Actions\LogEnvironmentDownloaded;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PullEnvironment extends Controller
{
    /**
     * GET /projects/{project}/environments/{name}/pull
     *
     * Returns an encrypted projection bundle (no plaintext).
     * Authorization: Requires 'ViewVariables' on the environment.
     *
     * Optional query params:
     *   - only[]=KEY      // restrict to specific variable names (repeatable)
     *   - include_meta=1  // include line_bytes/is_* flags in each entry
     *   - include_versions=1 // include each secret's head version in entries
     */
    public function __invoke(
        Request $request,
        Project $project,
        string $name,
        EnsureDeviceOwnership $ensureDeviceOwnership
    ): JsonResponse {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::ViewVariables]);

        $only = (array) $request->query('only', []);
        $includeMeta = (bool) filter_var($request->query('include_meta', false), FILTER_VALIDATE_BOOLEAN);
        $includeVersions = (bool) filter_var($request->query('include_versions', false), FILTER_VALIDATE_BOOLEAN);

        $bundle = app(BuildEncryptedProjection::class)->handle(
            environment: $env,
            only: $only,
            includeMeta: $includeMeta,
            includeVersions: $includeVersions
        );

        $onlyNames = collect($only)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();

        $device = $this->resolveRequestDevice($request, $ensureDeviceOwnership);

        if ($user = $request->user()) {
            $context = [
                'filters' => [
                    'only' => $onlyNames,
                    'only_count' => count($onlyNames),
                    'include_meta' => $includeMeta,
                    'include_versions' => $includeVersions,
                ],
                'secrets_returned' => count($bundle['secrets'] ?? []),
                'ip_address' => $request->ip(),
            ];

            if ($deviceContext = $this->makeDeviceProperties($device)) {
                $context['device'] = $deviceContext;
            }

            app(LogEnvironmentDownloaded::class)->handle(
                environment: $env,
                user: $user,
                source: 'cli',
                context: $context,
            );
        }

        // Optional: strong caching & ETag based on chain+HMACs
        return response()->json($bundle, 200);
    }

    /**
     * @throws ValidationException
     */
    private function resolveRequestDevice(
        Request $request,
        EnsureDeviceOwnership $ensureDeviceOwnership
    ): ?Device {
        $deviceId = $request->input('device_id') ?? $request->header('X-Device-ID');

        if (! $deviceId) {
            return null;
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

        /** @var \App\Account\Models\User|null $user */
        $user = $request->user();

        if ($user) {
            $ensureDeviceOwnership->handle($device, $user);
        }

        return $device;
    }

    private function makeDeviceProperties(?Device $device): ?array
    {
        if (! $device) {
            return null;
        }

        $properties = array_filter([
            'id' => (string) $device->id,
            'name' => $device->name,
            'platform' => $device->platform?->value,
            'app_version' => $device->app_version,
            'last_seen_at' => $device->last_seen_at?->toISOString(),
        ], static fn ($value) => $value !== null && $value !== '');

        return empty($properties) ? null : $properties;
    }
}
