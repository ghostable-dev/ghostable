<?php

use App\Billing\Livewire\InvoiceStatus;

it('exposes status helpers', function () {
    $component = new InvoiceStatus();
    $component->status = 'paid';

    expect($component->getIsPaidProperty())->toBeTrue()
        ->and($component->getIsRefundedProperty())->toBeFalse()
        ->and($component->getIsVoidProperty())->toBeTrue();

    $component->status = 'refunded';
    expect($component->getIsRefundedProperty())->toBeTrue()
        ->and($component->getIsPaidProperty())->toBeFalse();
});
