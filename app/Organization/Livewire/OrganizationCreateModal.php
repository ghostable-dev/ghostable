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
            <flux:modal name="create-organization" class="md:w-96" style="background:#ffffff;color:#18181b;">
                <form wire:submit="create" class="space-y-6" style="color:#18181b;">
                    <div>
                        <h2 style="font-size:1.125rem;line-height:1.5rem;font-weight:600;color:#18181b;">Create Organization</h2>
                    </div>
                    <div>
                        <label for="create-organization-name" style="display:block;margin-bottom:0.5rem;font-size:0.875rem;font-weight:500;color:#18181b;">
                            Name
                        </label>
                        <input
                            id="create-organization-name"
                            type="text"
                            wire:model="name"
                            required
                            style="display:block;width:100%;border-radius:0.375rem;border:1px solid #d4d4d8;background:#ffffff;padding:0.5rem 0.75rem;font-size:0.875rem;color:#18181b;" />
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost" style="color:#18181b;">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Create organization</flux:button>
                    </div>
                </form>
            </flux:modal>
        BLADE;
    }
}
