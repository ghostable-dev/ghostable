<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Concerns;

use App\Account\Models\User;

trait PresentsAuditActor
{
    private function presentAuditActor(?User $user): array
    {
        if ($user) {
            return [
                'type' => 'user',
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return [
            'type' => 'system',
        ];
    }
}
