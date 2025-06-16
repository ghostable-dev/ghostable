<?php

namespace App\Billing\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Events\WebhookReceived;

class StripeWebhookListener
{
    public function handle(WebhookHandled | WebhookReceived $event)
    {
        Log::info('Event: ' . $event->payload['type']);
        $id = $event->payload['data']['object']['customer'] ?? null;
        Log::info('ID: ' . $id ?? 'N/A');
        Log::info('--');
    }
}
