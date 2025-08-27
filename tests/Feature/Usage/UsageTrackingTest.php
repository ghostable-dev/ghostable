<?php

use App\Usage\Jobs\FoldUsageCounters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('records usage and folds counters into database', function () {
    Cache::store()->clear();

    $user = $this->createUser('Ray', 'ray@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);

    $token = $user->createToken('test');

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/v1/organizations/'.$organization->id)
        ->assertOk();

    // wait briefly to simulate delay before folding
    sleep(1);

    (new FoldUsageCounters())->handle();

    $endpoint = 'api/v1/organizations/'.$organization->id;
    $hour = Carbon::now('UTC')->startOfHour();
    $day = Carbon::now('UTC')->startOfDay();
    $tokenId = $token->accessToken->id;

    expect(DB::table('usage_hourly')->where([
        'token' => $tokenId,
        'endpoint' => $endpoint,
        'hour' => $hour,
        'count' => 1,
    ])->exists())->toBeTrue();

    expect(DB::table('usage_daily')->where([
        'token' => $tokenId,
        'endpoint' => $endpoint,
        'date' => $day,
        'count' => 1,
    ])->exists())->toBeTrue();
});

