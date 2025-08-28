<?php

namespace App\Billing\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Events\WebhookReceived;

class StripeWebhookListener
{
    public function handle(WebhookHandled|WebhookReceived $event)
    {
        Log::info('Stripe event received', [
            'event' => $event->payload['type'] ?? 'unknown',
            'customer_id' => $event->payload['data']['object']['customer'] ?? 'N/A',
        ]);
    }
}
