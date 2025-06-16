<?php

namespace App\Billing\Http\Controllers;

use App\Billing\Entities\StripePayload;
use App\Billing\Enums\SubscriptionType;
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
        if (!is_null($team = $data->teamFromStripeId())) {
            $type = $this->getSubscriptionTypeFromPayload($payload);
            SubscriptionStarted::dispatch($team, $type, $data);
        } else {
            Log::error('Stripe Webhook Error', [
                'method' => 'handleCustomerSubscriptionCreated',
                'payload' => $data
            ]);
        }
        
        return $response;
    }
    
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);
        
        $data = new StripePayload($payload);
        if (!is_null($team = $data->teamFromStripeId())) {
            $type = $this->getSubscriptionTypeFromPayload($payload);
            SubscriptionEnded::dispatch($team, $type, $data);
        } else {
            Log::error('Stripe Webhook Error', [
                'method' => 'handleCustomerSubscriptionDeleted',
                'payload' => $data
            ]);
        }
        
        return $response; 
    }
    
    protected function getSubscriptionTypeFromPayload(array $payload): ?SubscriptionType
    {
        if (is_null($id = $payload['data']['object']['plan']['id'] ?? null)) {
            Log::error('Could not find subscription type identifier.', compact('payload'));
            return null;
        }
        
        if (is_null($type = SubscriptionType::tryFromApiId($id))) {
            Log::error('Could not find subscription type for identifier.', compact('id', 'payload'));
            return null;
        }
        
        return $type;
    }
    
    protected function handleCheckoutSessionCompleted(array $payload)
    {
        $data = new StripePayload($payload);
        
        if (!is_null($team = $data->teamFromStripeId())) {
            
        } else {
            Log::error('Stripe Webhook Error', [
                'method' => 'handleCheckoutSessionCompleted',
                'payload' => $data
            ]);
        }
        
        return $this->successMethod();
    }
}
