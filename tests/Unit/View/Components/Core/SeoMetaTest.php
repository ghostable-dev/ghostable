<?php

use App\View\Components\Core\SeoMeta;
use Tests\TestCase;

uses(TestCase::class);

it('returns request url and default sharing image', function () {
    $this->get('/');

    $component = new SeoMeta(title: 'Title', description: 'Desc');

    expect($component->requestUrl())->toBe(url('/'))
        ->and($component->sharingImage())->toBe(asset('/images/ai-job-board-social.jpg'));
});

it('uses provided image when set', function () {
    $component = new SeoMeta(image: 'http://example.com/img.png');

    expect($component->sharingImage())->toBe('http://example.com/img.png');
});
