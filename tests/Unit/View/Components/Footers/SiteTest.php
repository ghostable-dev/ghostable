<?php

use App\View\Components\Footers\Site;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Route::get('/search')->name('search');
    Route::get('/pricing')->name('pricing');
    Route::get('/blog')->name('blog');
    Route::get('/terms')->name('terms');
    Route::get('/privacy')->name('privacy');
});

it('provides social links', function () {
    $component = new Site;

    expect($component->socialLinks())->toHaveCount(4);
});

it('provides resource links', function () {
    $component = new Site;

    expect($component->resourceLinks())->toHaveCount(4);
});

it('provides company links', function () {
    $component = new Site;

    expect($component->companyLinks())->toHaveCount(2);
});
