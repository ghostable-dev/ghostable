<?php

use App\Api\Usage\Actions\UpsertApiUsageDaily;
use App\Api\Usage\Actions\UpsertApiUsageHourly;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('upserts hourly usage counts', function () {
    $action = app(UpsertApiUsageHourly::class);
    $hour = Carbon::create(2025, 8, 27, 14, 0, 0, 'UTC');

    $action->handle('org', 'token', 'GET', '/endpoint', $hour, 1);
    $action->handle('org', 'token', 'GET', '/endpoint', $hour, 2);
    $action->handle('org', 'token', 'GET', '/endpoint', $hour, 4);

    $query = DB::table('api_usage_hourly')->where([
        'organization_id' => 'org',
        'token_id' => 'token',
        'method' => 'GET',
        'endpoint' => '/endpoint',
        'hour' => $hour,
    ]);

    $count = $query->value('count');
    $rows = $query->count();

    expect($count)->toBe(7)
        ->and($rows)->toBe(1);
});

it('upserts daily usage counts', function () {
    $action = app(UpsertApiUsageDaily::class);
    $day = Carbon::create(2025, 8, 27, 0, 0, 0, 'UTC');

    $action->handle('org', 'token', 'GET', '/endpoint', $day, 1);
    $action->handle('org', 'token', 'GET', '/endpoint', $day, 2);
    $action->handle('org', 'token', 'GET', '/endpoint', $day, 4);

    $query = DB::table('api_usage_daily')->where([
        'organization_id' => 'org',
        'token_id' => 'token',
        'method' => 'GET',
        'endpoint' => '/endpoint',
        'date' => $day,
    ]);

    $count = $query->value('count');
    $rows = $query->count();

    expect($count)->toBe(7)
        ->and($rows)->toBe(1);
});
