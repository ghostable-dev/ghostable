<?php

use App\Account\Models\User;
use Tests\TestCase;
use App\Billing\Notifications\SubscriptionEndedNotification;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('builds subscription ended mail message', function () {
    $org = Organization::factory()->create(['name' => 'Acme']);
    $user = User::factory()->make(['name' => 'Alice']);
    $notification = new SubscriptionEndedNotification($org);

    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Subscription ended')
        ->and($mail->greeting)->toBe($user->greeting())
        ->and($mail->introLines)->toBe([
            'The Ghostable subscription for "Acme" has ended.',
            'You are receiving this alert because you manage billing for this organization.',
        ]);
});
