<?php

declare(strict_types=1);

namespace App\Crypto\Actions;

use App\Crypto\Models\Device;
use Illuminate\Support\Facades\DB;

class DeleteDevice
{
    public function handle(Device $device): void
    {
        DB::transaction(function () use ($device) {
            $device->delete();
        });
    }
}
