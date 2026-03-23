<?php

use App\Auth\Enums\CliLoginSessionStatus;
use App\Auth\Models\CliLoginSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('cli register poll returns not found for unknown ticket', function () {
    $this->postJson('/api/v2/cli/register/poll', [
        'ticket' => (string) Str::uuid(),
    ])->assertNotFound()
        ->assertJson([
            'kind' => 'cancelled',
            'status' => 'not_found',
            'message' => 'Login session not found.',
        ]);
});

test('cli register poll returns pending status', function () {
    $session = CliLoginSession::create([
        'browser_token' => Str::random(64),
        'expires_at' => now()->addMinute(),
    ]);

    $this->postJson('/api/v2/cli/register/poll', [
        'ticket' => $session->id,
    ])->assertOk()
        ->assertJson([
            'kind' => 'unsupported',
            'status' => CliLoginSessionStatus::Pending->value,
        ]);
});

test('cli register poll returns expired status and marks session expired', function () {
    $session = CliLoginSession::create([
        'browser_token' => Str::random(64),
        'expires_at' => now()->subMinute(),
    ]);

    $this->postJson('/api/v2/cli/register/poll', [
        'ticket' => $session->id,
    ])->assertOk()
        ->assertJson([
            'kind' => 'expired',
            'status' => CliLoginSessionStatus::Expired->value,
        ]);

    expect($session->refresh()->status)->toBe(CliLoginSessionStatus::Expired);
});

test('cli register poll returns verification required status', function () {
    $session = CliLoginSession::create([
        'status' => CliLoginSessionStatus::VerificationRequired,
        'browser_token' => Str::random(64),
        'expires_at' => now()->addMinute(),
    ]);

    $this->postJson('/api/v2/cli/register/poll', [
        'ticket' => $session->id,
    ])->assertOk()
        ->assertJson([
            'kind' => 'verification_required',
            'status' => CliLoginSessionStatus::VerificationRequired->value,
        ]);
});

test('cli register poll returns approved status and token when available', function () {
    $session = CliLoginSession::create([
        'status' => CliLoginSessionStatus::Approved,
        'browser_token' => Str::random(64),
        'expires_at' => now()->addMinute(),
    ]);

    Cache::put($session->cacheKey(), 'test-token');

    $this->postJson('/api/v2/cli/register/poll', [
        'ticket' => $session->id,
    ])->assertOk()
        ->assertJson([
            'kind' => 'token',
            'status' => CliLoginSessionStatus::Approved->value,
            'token' => 'test-token',
        ]);
});
