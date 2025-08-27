<?php

use App\Api\Jobs\FoldUsageCounters;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('folds only tracked routes into hourly/daily aggregates', function () {
    // Use array cache so the dual-mode recorder takes the portable path
    config()->set('cache.default', 'array');
    Cache::store()->clear();

    // Freeze time so we know which minute bucket to process
    $tRequest = Carbon::create(2025, 8, 27, 14, 44, 10, 'UTC');
    Carbon::setTestNow($tRequest);

    // Seed: user, org, token
    $user = $this->createUser('Ray', 'ray@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);
    $token = $user->createToken('test');

    // ---------- Make two calls ----------
    // 1) UNTRACKED: /api/v1/organizations  (NOT wrapped in TrackUsage)
    $pathUntracked = '/api/v1/organizations';
    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson($pathUntracked)
        ->assertOk();

    // 2) TRACKED: /api/v1/organizations/{organization}/projects (wrapped in TrackUsage)
    $pathTracked = "/api/v1/organizations/{$organization->id}/projects";
    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson($pathTracked)
        ->assertOk();

    // Move to next minute; the job processes previous minutes
    Carbon::setTestNow($tRequest->copy()->addMinute()->startOfMinute());

    // Run the rollup
    (new FoldUsageCounters)->handle();

    // Expected time buckets
    $hour = $tRequest->copy()->startOfHour();
    $day = $tRequest->copy()->startOfDay();

    // Derive endpoints EXACTLY like middleware (no names = URI template)
    $routeTracked = app('router')->getRoutes()->match(Request::create($pathTracked, 'GET'));
    $endpointTracked = $routeTracked->getName() ?? $routeTracked->uri(); // "api/v1/organizations/{organization}/projects"

    $routeUntracked = app('router')->getRoutes()->match(Request::create($pathUntracked, 'GET'));
    $endpointUntracked = $routeUntracked->getName() ?? $routeUntracked->uri(); // "api/v1/organizations"

    $tokenId = (string) $token->accessToken->id;
    $orgId = (string) $organization->id;

    // ---------- Assertions ----------
    // Tracked route SHOULD be folded (aggregate row: resource fields NULL)
    expect(DB::table('api_usage_hourly')->where([
        'organization_id' => $orgId,
        'token_id' => $tokenId,
        'method' => 'GET',
        'endpoint' => $endpointTracked,
        'hour' => $hour,
        'count' => 1,
    ])->whereNull('resource_type')->whereNull('resource_id')->exists())->toBeTrue();

    expect(DB::table('api_usage_daily')->where([
        'organization_id' => $orgId,
        'token_id' => $tokenId,
        'method' => 'GET',
        'endpoint' => $endpointTracked,
        'date' => $day,
        'count' => 1,
    ])->whereNull('resource_type')->whereNull('resource_id')->exists())->toBeTrue();

    // Untracked route SHOULD NOT appear in aggregates
    expect(DB::table('api_usage_hourly')->where([
        'organization_id' => $orgId,
        'token_id' => $tokenId,
        'method' => 'GET',
        'endpoint' => $endpointUntracked,
        'hour' => $hour,
    ])->exists())->toBeFalse();

    expect(DB::table('api_usage_daily')->where([
        'organization_id' => $orgId,
        'token_id' => $tokenId,
        'method' => 'GET',
        'endpoint' => $endpointUntracked,
        'date' => $day,
    ])->exists())->toBeFalse();

    // Cleanup frozen time
    Carbon::setTestNow();
});
