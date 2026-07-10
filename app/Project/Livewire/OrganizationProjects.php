<?php

namespace App\Project\Livewire;

use App\Organization\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class OrganizationProjects extends Component
{
    public function mount(): void
    {
        abort_if($this->organization->usesDesktopLicensing(), 403);
    }

    #[Computed]
    public function projects(): LengthAwarePaginator
    {
        return $this->organization->projects()->paginate();
    }

    #[Computed(persist: true)]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[On('project-created')]
    public function refreshProjects(): void
    {
        $this->projects();
    }

    public function render()
    {
        return view('project.organization-projects');
    }
}
