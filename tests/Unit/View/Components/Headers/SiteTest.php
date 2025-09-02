<?php

namespace App\Core\Concerns;

if (! trait_exists(MakesLinks::class)) {
    trait MakesLinks
    {
        public function makeLink(string $url, string $label, string $icon = null, bool $active = false, string $target = '_self'): array
        {
            return compact('url', 'label', 'icon', 'active', 'target');
        }

        public function isRouteNameCurrent(string $name): bool
        {
            return false;
        }
    }
}

namespace Tests\Unit\View\Components\Headers;

use App\View\Components\Headers\Site;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Route::get('/search')->name('search');
    Route::get('/pricing')->name('pricing');
    Route::get('/blog')->name('blog');
});

it('returns primary site links', function () {
    $component = new Site();

    expect($component->primaryLinks())->toHaveCount(3);
});
