<?php

use App\Account\Models\User;
use App\Messaging\Enums\MessageStatus;
use App\Messaging\Registry\CampaignRegistry;
use App\Messaging\Series\OnboardingSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('onboarding series selects organization setup nudge first', function () {
    $series = OnboardingSeries::make();
    $registry = app(CampaignRegistry::class);

    $user = User::factory()->create();

    expect($series->nextKeyFor($user, $registry))->toBe('drip.organization-setup.v1');
});

test('onboarding series respects cooldown before sending another organization reminder', function () {
    $series = OnboardingSeries::make();
    $registry = app(CampaignRegistry::class);

    $now = Carbon::parse('2024-01-15 10:00:00');

    try {
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $primaryKey = 'drip.organization-setup.v1';

        Carbon::setTestNow($now->copy()->subDay());
        $user->messages()->create([
            'campaign_key' => $primaryKey,
            'status' => MessageStatus::SENT,
            'recipient_email' => $user->email,
        ]);

        Carbon::setTestNow($now);

        $nextKey = $series->nextKeyFor($user->fresh(), $registry);

        expect($nextKey)->toBeNull();
    } finally {
        Carbon::setTestNow();
    }
});

test('onboarding series offers reminder after cooldown has passed', function () {
    $series = OnboardingSeries::make();
    $registry = app(CampaignRegistry::class);

    $now = Carbon::parse('2024-01-15 10:00:00');

    try {
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $primaryKey = 'drip.organization-setup.v1';
        $reminderKey = 'drip.organization-setup-reminder.v1';

        Carbon::setTestNow($now->copy()->subDays(5));
        $user->messages()->create([
            'campaign_key' => $primaryKey,
            'status' => MessageStatus::SENT,
            'recipient_email' => $user->email,
        ]);

        Carbon::setTestNow($now);

        expect($series->nextKeyFor($user->fresh(), $registry))->toBe($reminderKey);
    } finally {
        Carbon::setTestNow();
    }
});

test('onboarding series advances when the organization step is complete', function () {
    $series = OnboardingSeries::make();
    $registry = app(CampaignRegistry::class);

    $user = User::factory()->create();

    $this->createOrganization('Acme Co', $user);

    expect($series->nextKeyFor($user->fresh(), $registry))->toBe('drip.cli-setup.v1');
});

test('onboarding series stops nudging after the max window', function () {
    $series = OnboardingSeries::make();
    $registry = app(CampaignRegistry::class);

    $now = Carbon::parse('2024-01-15 10:00:00');

    try {
        Carbon::setTestNow($now);

        $user = User::factory()->create();

        $user->forceFill([
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ])->save();

        expect($series->nextKeyFor($user->fresh(), $registry))->toBeNull();
    } finally {
        Carbon::setTestNow();
    }
});
