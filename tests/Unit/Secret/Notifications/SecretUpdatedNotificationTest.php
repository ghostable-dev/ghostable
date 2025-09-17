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

    expect($mail->subject)->toBe('Ghostable update: Secret "API_KEY" updated')
        ->and($mail->view)->toBe('mail.secret-updated')
        ->and($mail->viewData)->toMatchArray([
            'title' => 'Secret updated',
            'secret' => $secret,
        ]);

    expect($notification->toSlack($notifiable))->toBe('The "API_KEY" secret was updated on Ghostable.');
});
