<?php

use App\Account\Models\User;
use App\Secret\Models\Secret;
use App\Secret\Notifications\SecretUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('builds secret updated mail and slack messages', function () {
    $secret = new Secret(['name' => 'API_KEY']);

    $notification = new SecretUpdatedNotification($secret);
    $notifiable = User::factory()->make(['name' => 'Bob']);

    $mail = $notification->toMail($notifiable);

    expect($mail->subject)->toBe('Secret Updated')
        ->and($mail->greeting)->toBe($notifiable->greeting())
        ->and($mail->introLines)->toBe([
            "Secret 'API_KEY' was updated.",
        ]);

    expect($notification->toSlack($notifiable))->toBe("Secret 'API_KEY' was updated.");
});
