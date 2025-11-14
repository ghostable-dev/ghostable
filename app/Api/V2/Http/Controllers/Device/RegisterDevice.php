<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Device;

use App\Account\Models\User;
use App\Api\V2\Device\Presenters\DevicePresenter;
use App\Api\V2\Device\Requests\RegisterDeviceRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\LogDeviceActivity;
use App\Crypto\Actions\RegisterDevice as RegisterDeviceAction;
use Illuminate\Http\JsonResponse;

final class RegisterDevice extends Controller
{
    public function __invoke(
        RegisterDeviceRequest $request,
        RegisterDeviceAction $registerDevice,
        DevicePresenter $presenter,
        LogDeviceActivity $logDeviceActivity
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $device = $registerDevice->handle(
            user: $user,
            publicKey: $validated['public_key'],
            publicSigningKey: $validated['public_signing_key'],
            name: $validated['name'] ?? null,
            platform: $validated['platform'],
        );

        $logDeviceActivity->handle(
            device: $device,
            event: 'created',
            user: $user,
            context: [
                'source' => 'cli',
                'ip_address' => $request->ip(),
            ],
        );

        return response()->json(
            $presenter->present($device, ['public_key', 'platform', 'created_at']),
            201
        );
    }
}
