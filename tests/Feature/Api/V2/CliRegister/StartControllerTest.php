<?php

use App\Auth\Models\CliLoginSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('cli register start creates a session and returns expected payload', function () {
    Carbon::setTestNow(Carbon::parse('2024-01-01 00:00:00'));

    config([
        'cli-login.expires_in' => 600,
        'cli-login.poll_interval' => 5,
    ]);

    $response = $this->postJson('/api/v2/cli/register/start');

    $response->assertOk();

    $session = CliLoginSession::first();
    expect($session)->not->toBeNull();

    $response->assertJson([
        'ticket' => $session->id,
        'register_url' => route('register', ['ticket' => $session->id]),
        'poll_url' => url('/api/v2/cli/register/poll'),
        'poll_interval' => 5,
        'expires_at' => Carbon::now()->addSeconds(600)->toIso8601String(),
    ]);

    expect(Str::length($session->browser_token))->toBe(64);
});
