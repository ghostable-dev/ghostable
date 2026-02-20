<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Device;

use App\Account\Models\User;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\DeleteDevice as DeleteDeviceAction;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\LogDeviceActivity;
use App\Crypto\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteDevice extends Controller
{
    public function __invoke(
        Request $request,
        Device $device,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        DeleteDeviceAction $deleteDevice,
        LogDeviceActivity $logDeviceActivity
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $ensureDeviceOwnership->handle($device, $user);

        if (! $device->isRevoked()) {
            return response()->json([
                'message' => 'Device must be revoked before deletion.',
            ], 409);
        }

        $logDeviceActivity->handle(
            device: $device,
            event: 'deleted',
            user: $user,
            context: [
                'source' => 'cli',
                'ip_address' => $request->ip(),
            ],
        );

        $deleteDevice->handle($device);

        return response()->json([
            'meta' => ['success' => true],
        ], 200);
    }
}
