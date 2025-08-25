<?php

namespace App\Organization\Livewire;

use App\Organization\Actions\UpdateOrganizationName;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrganizationGeneralSettings extends Component
{
    public string $name;

    public function mount(): void
    {
        $this->name = $this->organization->name;
    }

    public function updateOrganizationName(): void
    {
        $this->authorize('manageSettings', $this->organization);

        app(UpdateOrganizationName::class)->handle($this->organization, $this->name);

        $this->organization->refresh();

        $this->dispatch('name-updated', name: $this->name);
    }

    #[Computed(persist: true)]
    public function canEditName(): bool
    {
        return true;
    }

    #[Computed()]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    /**
     * Permanently delete the organization.
     */
    public function deleteOrganization(): void
    {
        $this->authorize('manageSettings', $this->organization);

        $this->organization->delete();

        $this->redirect(route('dashboard'));
    }

    public function render()
    {
        return view('organization.organization-general-settings');
    }
}
