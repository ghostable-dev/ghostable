<?php

declare(strict_types=1);

namespace App\Licensing\Listeners;

use App\Licensing\Actions\FulfillStripeLicenseCheckout;
use Laravel\Cashier\Events\WebhookReceived;

class FulfillStripeLicenseCheckoutFromWebhook
{
    public function __construct(private FulfillStripeLicenseCheckout $fulfillCheckout) {}

    public function handle(WebhookReceived $event): void
    {
        $this->fulfillCheckout->executeFromWebhookPayload($event->payload);
    }
}
