<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Environment\Presenters\EnvironmentKeyPresenter;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Models\Device;
use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use App\Environment\Actions\ResolveLatestEnvironmentKey;
use App\Environment\Models\Environment;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class GetEnvironmentKey extends Controller
{
    public function __invoke(
        Request $request,
        Project $project,
        string $name,
        ResolveLatestEnvironmentKey $resolveLatestEnvironmentKey,
        ManageEnvironmentKeyReshareRequests $manageEnvironmentKeyReshareRequests,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        EnvironmentKeyPresenter $presenter
    ): JsonResponse {
        $environment = $project->environmentOrFail($name);

        $actor = $request->user();

        if ($actor instanceof Environment) {
            if ($environment->isNot($actor)) {
                abort(403);
            }
        } else {
            $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);
        }

        $environmentKey = $resolveLatestEnvironmentKey->handle($environment);

        if ($environmentKey === null) {
            return response()->json([
                'data' => null,
            ]);
        }

        $device = $this->resolveRequestDevice($request, $ensureDeviceOwnership);

        if ($device && $actor instanceof User) {
            $missingState = $manageEnvironmentKeyReshareRequests->resolveMissingKeyAccessState(
                environment: $environment,
                device: $device,
                triggerSource: 'key_fetch',
                actor: $actor,
                request: $request,
            );

            if ($missingState !== null) {
                return response()->json([
                    'error' => [
                        'code' => 'ENV_KEY_RESHARE_REQUIRED',
                        'detail' => 'Environment key access is pending key re-share for this device.',
                        ...$missingState,
                    ],
                ], 409);
            }
        }

        $environmentKey->load('envelope');

        return response()->json($presenter->present($environmentKey));
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

        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $ensureDeviceOwnership->handle($device, $user);
        }

        return $device;
    }
}
