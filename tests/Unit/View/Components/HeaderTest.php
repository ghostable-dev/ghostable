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

namespace Tests\Unit\View\Components;

use App\Account\Models\User;
use App\View\Components\Header;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Route::get('/')->name('home');
    Route::get('/pricing')->name('pricing');
    Route::get('/blog')->name('blog');
    Route::get('/account/{account}/jobs')->name('account.jobs');
});

it('returns primary links for non-organization user', function () {
    $user = \Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('isOrganization')->andReturn(false);

    $this->be($user);

    $component = new Header;

    expect($component->primaryLinks())->toHaveCount(3);
});

it('includes account links for organization user', function () {
    $user = \Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('isOrganization')->andReturn(true);
    $user->primaryAccount = (object) ['id' => 1];

    $this->be($user);

    $component = new Header;
    $links = $component->primaryLinks();

    expect($links[0]['label'])->toBe('My Jobs');
});
