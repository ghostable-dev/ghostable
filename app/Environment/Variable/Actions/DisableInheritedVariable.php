<?php

namespace App\Environment\Variable\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Variable\Entities\CreateVariableData;

class DisableInheritedVariable
{
    public function handle(
        string $key,
        Environment $environment,
        ?User $user = null
    ): void {
        $data = new CreateVariableData(
            environment: $environment,
            key: $key,
            value: '',
            is_deleted: true,
            createdBy: $user,
        );

        $var = resolve(CreateVariable::class)->handle(data: $data, silently: true);

        $var->logActivity('disabled-inherited', $user);
    }
}
