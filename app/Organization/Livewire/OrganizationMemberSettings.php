<?php

namespace App\Organization\Livewire;

use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrganizationMemberSettings extends Component
{
    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    public function render()
    {
        return view('organization.organization-member-settings');
    }
}
