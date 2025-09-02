<?php

namespace App\Job\Models;

class Job
{
}

namespace App\Core\Entities;

class Color
{
    public function __construct(public string $hex)
    {
    }
}

namespace Tests\Unit\View\Components\Job;

use App\View\Components\Job\ApplyButton;
use App\Job\Models\Job;
use App\Core\Entities\Color;
use Tests\TestCase;

uses(TestCase::class);

it('defaults color when none provided', function () {
    $component = new ApplyButton(job: new Job());

    expect($component->color->hex)->toBe('#000000');
});

it('uses provided color', function () {
    $color = new Color('#ffffff');
    $component = new ApplyButton(job: new Job(), color: $color);

    expect($component->color)->toBe($color);
});
