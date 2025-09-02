<?php

use App\Billing\Enums\Plan;
use App\Billing\Http\Controllers\ScaleCheckout;
use Tests\TestCase;

uses(TestCase::class);

it('returns the scale plan', function () {
    $controller = new ScaleCheckout();

    $plan = (fn () => $this->getBillablePlan())->call($controller);

    expect($plan)->toBe(Plan::SCALE);
});
