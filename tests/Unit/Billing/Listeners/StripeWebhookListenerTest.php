<?php

use App\Billing\Listeners\StripeWebhookListener;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookHandled;
use Tests\TestCase;

uses(TestCase::class);

it('logs stripe events', function () {
    Log::shouldReceive('info')->once()->with('Stripe event received', [
        'event' => 'customer.subscription.created',
        'customer_id' => 'cus_123',
    ]);

    $listener = new StripeWebhookListener;

    $event = new WebhookHandled([
        'type' => 'customer.subscription.created',
        'data' => ['object' => ['customer' => 'cus_123']],
    ]);

    $listener->handle($event);
});
