<?php

use App\Billing\Enums\Plan;
use App\Billing\Http\Controllers\StandardCheckout;
use Tests\TestCase;

uses(TestCase::class);

it('returns the standard plan', function () {
    $controller = new StandardCheckout();

    $plan = (fn () => $this->getBillablePlan())->call($controller);

    expect($plan)->toBe(Plan::STANDARD);
});
