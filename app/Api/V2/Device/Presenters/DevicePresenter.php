<?php

declare(strict_types=1);

namespace App\Api\V2\Device\Presenters;

use App\Crypto\Enums\DeviceClientType;
use App\Crypto\Models\Device;
use Illuminate\Support\Collection;

final class DevicePresenter
{
    /**
     * @param  list<string>|null  $only
     */
    public function present(Device $device, ?array $only = null): array
    {
        $clientType = $device->client_type instanceof DeviceClientType
            ? $device->client_type->value
            : $device->getRawOriginal('client_type');

        if (! is_string($clientType) || trim($clientType) === '') {
            $clientType = DeviceClientType::Cli->value;
        }

        $attributes = [
            'name' => $device->name,
            'public_key' => $device->public_key,
            'platform' => $device->platform?->value,
            'client_type' => $clientType,
            'status' => $device->isRevoked() ? 'revoked' : 'active',
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            'created_at' => $device->created_at?->toIso8601String(),
            'updated_at' => $device->updated_at?->toIso8601String(),
            'revoked_at' => $device->revoked_at?->toIso8601String(),
            'user_id' => (string) $device->user_id,
        ];

        if ($only !== null) {
            $attributes = array_intersect_key($attributes, array_flip($only));
        }

        return [
            'data' => [
                'type' => 'devices',
                'id' => (string) $device->getKey(),
                'attributes' => $attributes,
            ],
        ];
    }

    /**
     * @param  iterable<Device>|Collection<int, Device>  $devices
     * @param  list<string>|null  $only
     */
    public function presentCollection(iterable $devices, ?array $only = null): array
    {
        $collection = $devices instanceof Collection ? $devices : collect($devices);

        return [
            'data' => $collection
                ->map(fn (Device $device) => $this->present($device, $only)['data'])
                ->values()
                ->all(),
        ];
    }
}
