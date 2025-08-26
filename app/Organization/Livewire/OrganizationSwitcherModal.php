<?php

namespace App\Organization\Livewire;

use App\Organization\Actions\SwitchToOrganization;
use App\Organization\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrganizationSwitcherModal extends Component
{
    public bool $showing = false;

    public function mount(): void
    {
        $this->showing = session()->pull('show-organization-switcher', false)
            && Auth::user()->organizations()->count() > 1;
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Auth::user()->organizations;
    }

    public function switchToOrganization(Organization $organization): void
    {
        SwitchToOrganization::handle($organization);

        redirect()->route('dashboard');
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" :dismissible="false" class="md:w-96">
                <div class="space-y-6">
                    <flux:heading size="lg">Select a Organization</flux:heading>
                    <div class="space-y-2">
                        @foreach($this->organizations as $organization)
                            <flux:button class="w-full" wire:click="switchToOrganization('{{ $organization->id }}')">
                                {{ $organization->name }}
                            </flux:button>
                        @endforeach
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
