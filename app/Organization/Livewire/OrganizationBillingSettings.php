<?php

namespace App\Organization\Livewire;

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
}
