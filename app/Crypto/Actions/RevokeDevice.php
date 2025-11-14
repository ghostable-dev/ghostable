<?php

declare(strict_types=1);

namespace App\Crypto\Actions;

use App\Crypto\Models\Device;
use Illuminate\Support\Facades\DB;

class RevokeDevice
{
    public function handle(Device $device): Device
    {
        DB::transaction(function () use ($device) {
            $device->forceFill([
                'active' => false,
                'revoked_at' => now(),
            ])->save();
        });

        return $device->fresh();
    }
}
