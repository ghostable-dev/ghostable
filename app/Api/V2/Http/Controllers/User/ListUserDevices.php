<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\User;

use App\Account\Models\User;
use App\Api\V2\Device\Presenters\DevicePresenter;
use App\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListUserDevices extends Controller
{
    public function __invoke(Request $request, DevicePresenter $presenter): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $devices = $user->devices()
            ->orderByDesc('last_seen_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(
            $presenter->presentCollection($devices),
            200
        );
    }
}
