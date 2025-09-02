<?php

use App\Account\Models\User;
use App\Billing\Notifications\SubscriptionStartedNotification;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('builds subscription started mail message', function () {
    $org = Organization::factory()->create(['name' => 'Acme']);
    $user = User::factory()->make(['name' => 'Alice']);
    $notification = new SubscriptionStartedNotification($org);

    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Subscription activated')
        ->and($mail->greeting)->toBe($user->greeting())
        ->and($mail->introLines)->toBe([
            'The Ghostable subscription for "Acme" is now active.',
            'You are receiving this alert because you manage billing for this organization.',
        ]);
});
