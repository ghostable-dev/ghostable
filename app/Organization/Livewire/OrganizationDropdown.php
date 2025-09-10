<?php

namespace App\Organization\Livewire;

use App\Organization\Actions\SwitchToOrganization;
use App\Organization\Models\Organization;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrganizationDropdown extends Component
{
    #[Computed]
    public function currentOrganization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Auth::user()->organizations;
    }

    #[Computed]
    public function switchableOrganizations(): Collection
    {
        return Auth::user()
            ->organizations()
            ->where('organizations.id', '!=', $this->currentOrganization()->id)
            ->get();
    }

    public function switchToOrganization(Organization $organization): void
    {
        SwitchToOrganization::handle($organization);

        Flux::toast("Switched to the '{$organization->name}' organization.");

        redirect()->route('dashboard');
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <x-drop-button 
                    href="{{ route('dashboard') }}">
                    <span class="block max-w-[8rem] truncate text-left">
                        {{ $this->currentOrganization?->name }}
                    </span>
                    <x-slot name="menu">
                        <flux:menu>
                            <flux:menu.group heading="Manage Organization">
                                <flux:menu.item
                                    :href="route('organization.settings.index', $this->currentOrganization)"
                                    wire:navigate>Settings</flux:menu.item>
                            </flux:menu.group>
                            <flux:menu.group heading="Switch Organizations">
                                
                                @foreach($this->switchableOrganizations as $organization)
                                    <flux:menu.item 
                                        wire:click="switchToOrganization('{{ $organization->id }}')" 
                                        wire:key="organization-{{ $organization->id }}">
                                        {{ $organization->name }}
                                    </flux:menu.item>
                                @endforeach
                                
                            </flux:menu.group>
                            <flux:menu.group>
                                <flux:modal.trigger name="create-organization">
                                    <flux:menu.item icon="plus">
                                        New
                                    </flux:menu.item>
                                </flux:modal.trigger>
                            </flux:menu.group>
                        </flux:menu>
                    </x-slot>
                </x-drop-button>
                <livewire:organization.livewire.organization-create-modal/>
            </div>
        BLADE;
    }
}
