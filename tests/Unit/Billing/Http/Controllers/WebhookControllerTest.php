<?php

use App\Billing\Enums\Plan;
use App\Billing\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('returns plan from payload when valid id provided', function () {
    config()->set('platform.billing.standard.api_id', 'price_std');
    $controller = new class extends WebhookController
    {
        public function publicGet(array $payload)
        {
            return $this->getSubscriptionPlanFromPayload($payload);
        }
    };
    $payload = [
        'data' => ['object' => ['plan' => ['id' => 'price_std']]],
    ];
    expect($controller->publicGet($payload))->toBe(Plan::STANDARD);
});

it('logs error when plan id missing', function () {
    $controller = new class extends WebhookController
    {
        public function publicGet(array $payload)
        {
            return $this->getSubscriptionPlanFromPayload($payload);
        }
    };
    Log::shouldReceive('error')->once();
    $payload = ['data' => ['object' => []]];
    expect($controller->publicGet($payload))->toBeNull();
});

it('logs error when plan id invalid', function () {
    config()->set('platform.billing.standard.api_id', 'price_std');
    $controller = new class extends WebhookController
    {
        public function publicGet(array $payload)
        {
            return $this->getSubscriptionPlanFromPayload($payload);
        }
    };
    Log::shouldReceive('error')->once();
    $payload = ['data' => ['object' => ['plan' => ['id' => 'price_invalid']]]];
    expect($controller->publicGet($payload))->toBeNull();
});
