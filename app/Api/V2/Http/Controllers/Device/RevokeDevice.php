<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Device;

use App\Account\Models\User;
use App\Api\V2\Device\Presenters\DevicePresenter;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Actions\LogDeviceActivity;
use App\Crypto\Actions\RevokeDevice as RevokeDeviceAction;
use App\Crypto\Models\Device;
use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RevokeDevice extends Controller
{
    public function __invoke(
        Request $request,
        Device $device,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        RevokeDeviceAction $revokeDevice,
        DevicePresenter $presenter,
        LogDeviceActivity $logDeviceActivity,
        ManageEnvironmentKeyReshareRequests $manageEnvironmentKeyReshareRequests
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $ensureDeviceOwnership->handle($device, $user);

        $revokedDevice = $revokeDevice->handle($device);

        $logDeviceActivity->handle(
            device: $revokedDevice,
            event: 'revoked',
            user: $user,
            context: [
                'source' => 'cli',
                'ip_address' => $request->ip(),
            ],
        );

        $manageEnvironmentKeyReshareRequests->cancelForDevice(
            device: $revokedDevice,
            reason: 'device_revoked',
            actor: $user,
            request: $request,
            triggerSource: 'device_revoke',
        );

        return response()->json(array_merge(
            $presenter->present($revokedDevice, ['status', 'revoked_at']),
            ['meta' => ['success' => true]]
        ));
    }
}
