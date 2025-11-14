<?php

declare(strict_types=1);

namespace App\Crypto\Actions;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use Illuminate\Auth\Access\AuthorizationException;

final class EnsureDeviceOwnership
{
    /**
     * @throws AuthorizationException
     */
    public function handle(Device $device, User $user): void
    {
        if ($device->user_id !== $user->getKey()) {
            throw new AuthorizationException('You do not have access to this device.');
        }
    }
}
