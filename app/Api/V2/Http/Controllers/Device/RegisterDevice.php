<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Device;

use App\Account\Models\User;
use App\Api\V2\Device\Presenters\DevicePresenter;
use App\Api\V2\Device\Requests\RegisterDeviceRequest;
use App\Core\Http\Controllers\Controller;
use App\Crypto\Actions\LogDeviceActivity;
use App\Crypto\Actions\RegisterDevice as RegisterDeviceAction;
use App\Crypto\Enums\DeviceClientType;
use App\Crypto\Enums\DevicePlatform;
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
        $platformInput = strtolower(trim((string) ($validated['platform'] ?? '')));
        $clientTypeInput = strtolower(trim((string) ($validated['client_type'] ?? '')));

        // Backward compatibility: older clients sent "cli" in platform.
        if ($clientTypeInput === '' && in_array($platformInput, ['cli', 'desktop'], true)) {
            $clientTypeInput = $platformInput;
        }

        $platform = match ($platformInput) {
            '', 'cli', 'desktop', 'other' => DevicePlatform::Unknown->value,
            'mac', 'macosx', 'darwin', 'osx' => DevicePlatform::MacOS->value,
            default => DevicePlatform::fromStorageValue($platformInput)->value,
        };

        $clientType = DeviceClientType::tryFrom($clientTypeInput)?->value
            ?? DeviceClientType::Cli->value;

        $device = $registerDevice->handle(
            user: $user,
            publicKey: $validated['public_key'],
            publicSigningKey: $validated['public_signing_key'],
            name: $validated['name'] ?? null,
            platform: $platform,
            clientType: $clientType,
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
