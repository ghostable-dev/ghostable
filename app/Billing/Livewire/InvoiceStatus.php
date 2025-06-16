<?php

namespace App\Billing\Livewire;

use Livewire\Attributes\Lazy;
use Livewire\Component;
use Stripe\StripeClient;

#[Lazy]
class InvoiceStatus extends Component
{
    public string $status;

    public function mount(string $invoiceId): void
    {
        $stripe = new StripeClient(config('cashier.secret'));

        $invoice = $stripe->invoices->retrieve($invoiceId);

        $this->status = $invoice->status;

        $paymentIntentId = $invoice->payment_intent;
        if ($paymentIntentId) {
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);
            $latestChargeId = $paymentIntent->latest_charge;
            if ($latestChargeId) {
                $charge = $stripe->charges->retrieve($latestChargeId, [
                    'expand' => ['refunds'],
                ]);
                if (! empty($charge->refunds->data)) {
                    $this->status = 'refunded';
                }
            }
        }
    }

    public function placeholder()
    {
        return <<<'HTML'
        <span 
            class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
            Loading
        </span>
        HTML;
    }

    public function getIsPaidProperty(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsVoidProperty(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsRefundedProperty(): bool
    {
        return $this->status === 'refunded';
    }

    public function render()
    {
        return view('billing.livewire.invoice-status');
    }
}
