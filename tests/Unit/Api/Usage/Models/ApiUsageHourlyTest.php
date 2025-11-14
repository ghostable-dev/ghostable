<?php

use App\Api\Usage\Models\ApiUsageHourly;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('casts hour to carbon', function () {
    $usage = new ApiUsageHourly(['hour' => '2024-05-27 15:00:00']);

    expect($usage->hour)->toBeInstanceOf(Carbon::class);
});
