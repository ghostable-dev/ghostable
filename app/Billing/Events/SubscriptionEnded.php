<?php

namespace App\Billing\Events;

use App\Billing\Entities\StripePayload;
use App\Billing\Enums\SubscriptionType;
use App\Organization\Models\Organization;

class SubscriptionEnded extends StripeEvent
{
    public function __construct(
        public Organization $organization,
        public SubscriptionType $type,
        public ?StripePayload $data = null,
    ) {}
}
