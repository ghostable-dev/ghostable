<?php

namespace App\Environment\Versioning\Actions;

use App\Account\Models\User;
use App\Environment\Versioning\Actions\CreateVariableVersion;
use App\Environment\Versioning\Models\EnvironmentVariableVersion;

class RestoreVariableVersion
{
    public function __construct(
        protected CreateVariableVersion $createVersion
    ) {}

    public function handle(
        EnvironmentVariableVersion $version, 
        ?User $restoredBy = null
    ): void
    {
        $variable = $version->variable;
        
        $variable->update([
            'value' => $version->value,
            'last_updated_at' => now(),
            'last_updated_by' => $restoredBy?->id,
        ]);

        $variable->createVersionBy($restoredBy);

        $variable->logActivity('restored', user: $restoredBy);
    }
}