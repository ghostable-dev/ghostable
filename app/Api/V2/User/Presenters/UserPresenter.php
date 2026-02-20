<?php

declare(strict_types=1);

namespace App\Api\V2\User\Presenters;

use App\Account\Models\User;

final class UserPresenter
{
    public function present(User $user): array
    {
        return [
            'data' => [
                'type' => 'users',
                'id' => (string) $user->getKey(),
                'attributes' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at?->toIso8601String(),
                    'updated_at' => $user->updated_at?->toIso8601String(),
                ],
            ],
        ];
    }
}
