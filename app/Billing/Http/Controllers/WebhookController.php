<?php

namespace App\Billing\Http\Controllers;

use App\Billing\Entities\StripePayload;
use App\Billing\Enums\Plan;
use App\Billing\Events\SubscriptionEnded;
use App\Billing\Events\SubscriptionStarted;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class WebhookController extends CashierWebhookController
{
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        $data = new StripePayload($payload);
        if (! is_null($organization = $data->organizationFromStripeId())) {
            $plan = $this->getSubscriptionPlanFromPayload($payload);
            SubscriptionStarted::dispatch($organization, $plan, $data);
        } else {
            Log::error('Stripe Webhook Error', [
                'method' => 'handleCustomerSubscriptionCreated',
                'payload' => $data,
            ]);
        }

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $data = new StripePayload($payload);
        if (! is_null($organization = $data->organizationFromStripeId())) {
            $plan = $this->getSubscriptionPlanFromPayload($payload);
            SubscriptionEnded::dispatch($organization, $plan, $data);
        } else {
            Log::error('Stripe Webhook Error', [
                'method' => 'handleCustomerSubscriptionDeleted',
                'payload' => $data,
            ]);
        }

        return $response;
    }

    protected function getSubscriptionPlanFromPayload(array $payload): ?Plan
    {
        if (is_null($id = $payload['data']['object']['plan']['id'] ?? null)) {
            Log::error('Could not find subscription plan identifier.', compact('payload'));

            return null;
        }

        if (is_null($plan = Plan::tryFromBillableId($id))) {
            Log::error('Could not find subscription plan for identifier.', compact('id', 'payload'));

            return null;
        }

        return $plan;
    }

    protected function handleCheckoutSessionCompleted(array $payload)
    {
        $data = new StripePayload($payload);

        if (! is_null($organization = $data->organizationFromStripeId())) {

        } else {
            Log::error('Stripe Webhook Error', [
                'method' => 'handleCheckoutSessionCompleted',
                'payload' => $data,
            ]);
        }

        return $this->successMethod();
    }
}
