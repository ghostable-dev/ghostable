<?php

namespace App\Account\Managers;

use App\Account\Entities\Role;
use App\Account\Enums\Permission;

class ACLManager
{
    /**
     * @var Role[]
     */
    protected static $roles = [];

    /**
     * @var Permission[]
     */
    protected static $permissions = [];

    /**
     * @param  Permission[]  $permissions
     */
    public static function defineRole(string $key, string $name, array $permissions): Role
    {
        static::updatePermissions($permissions);

        return tap(new Role($key, $name, $permissions), function ($role) use ($key) {
            static::$roles[$key] = $role;
        });
    }

    /**
     * @param  Permission[]  $permissions
     */
    protected static function updatePermissions(array $permissions): void
    {
        $merged = array_merge(static::$permissions, $permissions);

        static::$permissions = collect($merged)->unique()->sort()->values()->all();
    }

    /**
     * @return Permission[]
     */
    public static function getRoles(): array
    {
        return static::$roles;
    }

    public static function getRole(?string $key): ?Role
    {
        return static::$roles[$key] ?? null;
    }

    public static function hasPermission(Permission $permission): bool
    {
        return in_array($permission, static::$permissions);
    }
}
