<?php

namespace App\Account\Managers;

if (! class_exists(ACLManager::class)) {
    class ACLManager
    {
        public static function getRoles(): array
        {
            return [
                (object) [
                    'key' => 'admin',
                    'name' => 'Admin',
                    'description' => 'Administrator',
                ],
            ];
        }
    }
}

namespace Tests\Unit\View\Components\Account;

use App\Account\Managers\ACLManager;
use App\View\Components\Account\RoleSelect;
use Tests\TestCase;

uses(TestCase::class);

it('returns roles from ACLManager', function () {
    $component = new RoleSelect;

    expect($component->roles())->toEqual(ACLManager::getRoles());
});
