<?php

namespace App\Account\Casts;

use App\Account\Entities\Role;
use App\Account\Managers\ACLManager;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class RoleCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Role
    {
        if (! is_null($value)) {
            return ACLManager::getRole($value);
        }

        return null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_a($value, Role::class)) {
            return $value->key;
        }

        return $value;
    }
}
