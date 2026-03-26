<?php

namespace App\Organization\Livewire;

use App\Billing\Enums\Plan;
use App\Organization\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrganizationBillingSettings extends Component
{
    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[Computed]
    public function invoices(): Collection
    {
        $this->authorize('manageBilling', $this->organization);

        return $this->organization->invoices();
    }

    /**
     * @return array{google_tag_id:string,send_to:string,value:float,currency:string,transaction_id:string}|null
     */
    #[Computed]
    public function googleAdsSubscriptionStartedConversion(): ?array
    {
        $googleTagId = config('services.google_tag.id');
        $subscriptionStartedLabel = config('services.google_tag.subscription_started_label');
        $checkoutState = (string) request()->query('checkout');
        $checkoutSessionId = (string) request()->query('checkout_session_id');
        $plan = Plan::tryFrom((string) request()->query('plan'));

        if (
            blank($googleTagId)
            || blank($subscriptionStartedLabel)
            || $checkoutState !== 'success'
            || blank($checkoutSessionId)
            || ! str_starts_with($checkoutSessionId, 'cs_')
            || ! $plan?->isBillable()
        ) {
            return null;
        }

        $value = $this->purchaseConversionValue($plan);
        if ($value === null) {
            return null;
        }

        return [
            'google_tag_id' => $googleTagId,
            'send_to' => "{$googleTagId}/{$subscriptionStartedLabel}",
            'value' => $value,
            'currency' => 'USD',
            'transaction_id' => $checkoutSessionId,
        ];
    }

    public function download(string $invoiceId)
    {
        $this->authorize('manageBilling', $this->organization);

        $invoice = $this->organization->findInvoice($invoiceId);

        return response()->streamDownload(function () use ($invoice) {
            echo $invoice->download();
        }, 'ghostable-invoice-'.str($invoice->date(timezone())).'.pdf');
    }

    public function render()
    {
        return view('organization.organization-billing-settings');
    }

    private function purchaseConversionValue(Plan $plan): ?float
    {
        return match ($plan) {
            Plan::STANDARD => 15.0,
            Plan::SCALE => 50.0,
            default => null,
        };
    }
}
