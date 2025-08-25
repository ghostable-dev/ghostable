<?php

namespace App\Organization\Livewire;

use App\Organization\Actions\CreateOrganization;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrganizationCreateModal extends Component
{
    public string $name = '';

    public function create()
    {
        CreateOrganization::handle(
            name: $this->name,
            owner: Auth::user()
        );

        $this->name = '';

        Flux::modal('create-organization')->close();
        Flux::toast('New organization has been created.');

        redirect(route('dashboard'));
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal name="create-organization" class="md:w-96">
                <form wire:submit="create" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Create Organization</flux:heading>
                        <flux:text class="mt-2"></flux:text>
                    </div>
                    <div>
                        <flux:input label="Name" wire:model="name" required />
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Create organization</flux:button>
                    </div>
                </form>
            </flux:modal>
        BLADE;
    }
}
