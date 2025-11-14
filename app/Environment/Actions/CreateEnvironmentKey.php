<?php

declare(strict_types=1);

namespace App\Environment\Actions;

use App\Crypto\Models\Device;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateEnvironmentKey
{
    public function handle(
        Environment $environment,
        string $fingerprint,
        ?Device $createdByDevice = null,
        ?int $version = null,
        ?Carbon $rotatedAt = null
    ): EnvironmentKey {
        return DB::transaction(function () use ($environment, $fingerprint, $createdByDevice, $version, $rotatedAt) {
            $nextVersion = $version;

            if ($nextVersion === null) {
                $currentMax = $environment->keys()->max('version');
                $nextVersion = ($currentMax ?? 0) + 1;
            }

            return $environment->keys()->create([
                'version' => $nextVersion,
                'fingerprint' => $fingerprint,
                'created_by_device_id' => $createdByDevice?->getKey(),
                'rotated_at' => $rotatedAt,
            ]);
        });
    }
}
