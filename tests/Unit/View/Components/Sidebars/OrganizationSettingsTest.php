<?php

namespace App\Core\Concerns;

if (! trait_exists(MakesLinks::class)) {
    trait MakesLinks
    {
        public function makeLink(string $url, string $label, ?string $icon = null, bool $active = false, string $target = '_self'): array
        {
            return compact('url', 'label', 'icon', 'active', 'target');
        }

        public function isRouteNameCurrent(string $name): bool
        {
            return false;
        }
    }
}

namespace App\Account\Managers;

if (! class_exists(AccountSwitcher::class)) {
    class AccountSwitcher
    {
        public static $account = null;

        public static function get()
        {
            return static::$account;
        }
    }
}

namespace App\Account\Models;

if (! class_exists(Account::class)) {
    class Account
    {
        public function __construct(public $id) {}
    }
}

namespace Tests\Unit\View\Components\Sidebars;

use App\Account\Models\Account;
use App\Account\Models\User;
use App\View\Components\Sidebars\OrganizationSettings;
use Tests\TestCase;

uses(TestCase::class);

it('returns account for account holder', function () {
    $account = new Account(1);
    $user = \Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('isAccountHolder')->andReturn(true);
    $user->primaryAccount = $account;
    $this->be($user);

    $component = new OrganizationSettings;
    $method = (new \ReflectionClass($component))->getMethod('getAccount');
    $method->setAccessible(true);

    expect($method->invoke($component))->toBe($account);
});

it('returns null when no account', function () {
    \App\Account\Managers\AccountSwitcher::$account = null;
    $user = \Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('isAccountHolder')->andReturn(false);
    $this->be($user);

    $component = new OrganizationSettings;
    $method = (new \ReflectionClass($component))->getMethod('getAccount');
    $method->setAccessible(true);

    expect($method->invoke($component))->toBeNull();
});
