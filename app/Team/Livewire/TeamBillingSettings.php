<?php

namespace App\Team\Livewire;

use App\Team\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamBillingSettings extends Component
{
    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }

    #[Computed]
    public function invoices(): Collection
    {
        $this->authorize('manageBilling', $this->team);

        return $this->team->invoices();
    }

    public function download(string $invoiceId)
    {
        $this->authorize('manageBilling', $this->team);

        $invoice = $this->team->findInvoice($invoiceId);

        return response()->streamDownload(function () use ($invoice) {
            echo $invoice->download();
        }, 'ghostable-invoice-'.str($invoice->date(timezone())).'.pdf');
    }

    public function render()
    {
        return view('team.team-billing-settings');
    }
}
