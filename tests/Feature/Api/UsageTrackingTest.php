<?php

use App\Api\Jobs\FoldUsageCounters;
use App\Environment\Enums\EnvironmentType;
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

    $endpointTracked = ltrim($pathTracked, '/');
    $endpointUntracked = ltrim($pathUntracked, '/');

    $tokenId = (string) $token->accessToken->id;
    $orgId = (string) $organization->id;

    // ---------- Assertions ----------
    // Tracked route SHOULD be folded
    expect(DB::table('api_usage_hourly')->where([
        'organization_id' => $orgId,
        'token_id' => $tokenId,
        'method' => 'GET',
        'endpoint' => $endpointTracked,
        'hour' => $hour,
        'count' => 1,
    ])->exists())->toBeTrue();

    expect(DB::table('api_usage_daily')->where([
        'organization_id' => $orgId,
        'token_id' => $tokenId,
        'method' => 'GET',
        'endpoint' => $endpointTracked,
        'date' => $day,
        'count' => 1,
    ])->exists())->toBeTrue();

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

it('keys counts by full endpoint path', function () {
    config()->set('cache.default', 'array');
    Cache::store()->clear();

    $tRequest = Carbon::create(2025, 8, 27, 14, 44, 10, 'UTC');
    Carbon::setTestNow($tRequest);

    $user = $this->createUser('Ray', 'ray@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);
    $project = $this->createProject('Website', $organization);
    $envA = $this->createEnvironment('dev', EnvironmentType::DEVELOPMENT, $project);
    $envB = $this->createEnvironment('prod', EnvironmentType::PRODUCTION, $project);
    $token = $user->createToken('test');

    $path = "/api/v1/projects/{$project->id}/environments/%s";
    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson(sprintf($path, $envA->name))
        ->assertOk();
    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson(sprintf($path, $envB->name))
        ->assertOk();

    Carbon::setTestNow($tRequest->copy()->addMinute()->startOfMinute());
    (new FoldUsageCounters)->handle();

    $hour = $tRequest->copy()->startOfHour();
    $day = $tRequest->copy()->startOfDay();
    $tokenId = (string) $token->accessToken->id;
    $orgId = (string) $organization->id;

    $endpointA = ltrim(sprintf($path, $envA->name), '/');
    $endpointB = ltrim(sprintf($path, $envB->name), '/');

    foreach ([$endpointA, $endpointB] as $endpoint) {
        expect(DB::table('api_usage_hourly')->where([
            'organization_id' => $orgId,
            'token_id' => $tokenId,
            'method' => 'GET',
            'endpoint' => $endpoint,
            'hour' => $hour,
            'count' => 1,
        ])->exists())->toBeTrue();
    }

    $template = 'api/v1/projects/{project}/environments/{name}';
    expect(DB::table('api_usage_hourly')->where([
        'organization_id' => $orgId,
        'token_id' => $tokenId,
        'method' => 'GET',
        'endpoint' => $template,
        'hour' => $hour,
    ])->exists())->toBeFalse();

    expect(DB::table('api_usage_daily')->where([
        'organization_id' => $orgId,
        'token_id' => $tokenId,
        'method' => 'GET',
        'endpoint' => $template,
        'date' => $day,
    ])->exists())->toBeFalse();

    Carbon::setTestNow();
});
