<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Device;

use App\Account\Models\User;
use App\Api\V2\Device\Presenters\DevicePresenter;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\EnsureDeviceOwnership;
use App\Crypto\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowDevice extends Controller
{
    public function __invoke(
        Request $request,
        Device $device,
        EnsureDeviceOwnership $ensureDeviceOwnership,
        DevicePresenter $presenter
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $ensureDeviceOwnership->handle($device, $user);

        return response()->json(
            $presenter->present($device, ['public_key', 'platform', 'status', 'last_seen_at', 'revoked_at', 'created_at'])
        );
    }
}
