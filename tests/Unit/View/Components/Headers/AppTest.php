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

namespace App\Account\Models;

if (! class_exists(Account::class)) {
    class Account
    {
        public function __construct(public $id) {}
    }
}

namespace Tests\Unit\View\Components\Headers;

use App\Account\Models\Account;
use App\Account\Models\User;
use App\View\Components\Headers\App as AppHeader;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Route::get('/candidate/home')->name('candidate.home');
    Route::get('/candidate/jobs')->name('candidate.jobs');
    Route::get('/candidate/bookmarks')->name('candidate.bookmarks');
    Route::get('/candidate/preferences/general')->name('candidate.preferences.general');
    Route::get('/account/{account}/jobs')->name('account.jobs');
    Route::get('/account/{account}/team')->name('account.team');
    Route::get('/account/{account}/organization')->name('account.organization.overview');
    Route::get('/account/{account}/settings')->name('account.settings.billing');
});

it('returns candidate links for candidate user', function () {
    $user = \Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('isCandidate')->andReturn(true);
    $user->shouldReceive('isFounder')->andReturn(false);

    $component = new AppHeader;

    $links = $component->for($user);

    expect($links)->toHaveCount(4);
});

it('returns organization links for founder', function () {
    $user = \Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('isFounder')->andReturn(true);
    $user->shouldReceive('primaryRoleIsAdmin')->andReturn(true);
    $user->shouldReceive('primaryRoleIsJobManager')->andReturn(true);
    $user->shouldReceive('primaryRoleIsBillingManager')->andReturn(true);

    $account = new Account(1);

    $component = new AppHeader;

    $links = $component->orgLinks($user, $account);

    expect($links)->toHaveCount(4);
});
