<?php

use App\Api\Actions\UpsertApiUsageDaily;
use App\Api\Actions\UpsertApiUsageHourly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('upserts hourly usage counts', function () {
    $action = app(UpsertApiUsageHourly::class);
    $hour = Carbon::create(2025, 8, 27, 14, 0, 0, 'UTC');

    $action->handle('org', 'token', 'GET', '/endpoint', $hour, 1);
    $action->handle('org', 'token', 'GET', '/endpoint', $hour, 2);
    $action->handle('org', 'token', 'GET', '/endpoint', $hour, 4, 'res', 'id');

    $aggQuery = DB::table('api_usage_hourly')->where([
        'organization_id' => 'org',
        'token_id' => 'token',
        'method' => 'GET',
        'endpoint' => '/endpoint',
        'resource_type' => null,
        'resource_id' => null,
        'hour' => $hour,
    ]);

    $agg = $aggQuery->value('count');
    $aggRows = $aggQuery->count();

    $res = DB::table('api_usage_hourly')->where([
        'organization_id' => 'org',
        'token_id' => 'token',
        'method' => 'GET',
        'endpoint' => '/endpoint',
        'resource_type' => 'res',
        'resource_id' => 'id',
        'hour' => $hour,
    ])->value('count');

    expect($agg)->toBe(3)
        ->and($aggRows)->toBe(1)
        ->and($res)->toBe(4);
});

it('upserts daily usage counts', function () {
    $action = app(UpsertApiUsageDaily::class);
    $day = Carbon::create(2025, 8, 27, 0, 0, 0, 'UTC');

    $action->handle('org', 'token', 'GET', '/endpoint', $day, 1);
    $action->handle('org', 'token', 'GET', '/endpoint', $day, 2);
    $action->handle('org', 'token', 'GET', '/endpoint', $day, 4, 'res', 'id');

    $aggQuery = DB::table('api_usage_daily')->where([
        'organization_id' => 'org',
        'token_id' => 'token',
        'method' => 'GET',
        'endpoint' => '/endpoint',
        'resource_type' => null,
        'resource_id' => null,
        'date' => $day,
    ]);

    $agg = $aggQuery->value('count');
    $aggRows = $aggQuery->count();

    $res = DB::table('api_usage_daily')->where([
        'organization_id' => 'org',
        'token_id' => 'token',
        'method' => 'GET',
        'endpoint' => '/endpoint',
        'resource_type' => 'res',
        'resource_id' => 'id',
        'date' => $day,
    ])->value('count');

    expect($agg)->toBe(3)
        ->and($aggRows)->toBe(1)
        ->and($res)->toBe(4);
});
