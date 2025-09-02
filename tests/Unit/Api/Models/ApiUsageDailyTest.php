<?php

use App\Api\Models\ApiUsageDaily;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('casts date to carbon', function () {
    $usage = new ApiUsageDaily(['date' => '2024-05-27']);

    expect($usage->date)->toBeInstanceOf(Carbon::class);
});
