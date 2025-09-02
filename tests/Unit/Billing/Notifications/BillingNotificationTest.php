<?php

use App\Account\Models\User;
use App\Billing\Notifications\SubscriptionStartedNotification;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('routes via mail and returns organization id in array', function () {
    $org = Organization::factory()->create();
    $notification = new SubscriptionStartedNotification($org);
    $notifiable = User::factory()->create();

    expect($notification->via($notifiable))->toBe(['mail'])
        ->and($notification->toArray($notifiable))->toBe([
            'organization_id' => $org->id,
        ]);
});
