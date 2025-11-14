<?php

declare(strict_types=1);

namespace App\Crypto\Actions;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use Illuminate\Support\Facades\DB;

final class RegisterDevice
{
    public function handle(
        User $user,
        string $publicKey,
        string $publicSigningKey,
        ?string $name,
        string $platform
    ): Device {
        return DB::transaction(function () use ($user, $publicKey, $publicSigningKey, $name, $platform) {
            return $user->devices()->create([
                'public_key' => $publicKey,
                'public_signing_key' => $publicSigningKey,
                'name' => $name,
                'platform' => $platform,
                'active' => true,
            ]);
        });
    }
}
