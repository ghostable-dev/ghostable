<x-slot name="subheader">
    @if(auth()->check() && auth()->user()->organizations->count())
        <div class="w-full bg-white pt-2">
            <div class="w-full px-6 lg:px-8">
                <flux:navbar>
                    <flux:navbar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        Overview
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('projects')" :current="request()->routeIs('projects')" wire:navigate>
                        Projects
                    </flux:navbar.item>
                    <flux:navbar.item
                        :href="route('organization.settings.general')"
                        :current="request()->routeIs('organization.settings.*')"
                        wire:navigate>
                        Settings
                    </flux:navbar.item>
                </flux:navbar>
            </div>
        </div>
    @endif
</x-slot>

<div class="flex items-start max-md:flex-col">
    
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist variant="sidebar">
            
            @php
                $links = [
                    ['route' => 'organization.settings.general', 'label' => 'General'],
                    ['route' => 'organization.settings.billing', 'label' => 'Billing'],
                    ['route' => 'organization.settings.members', 'label' => 'Members'],
                    ['route' => 'organization.settings.notifications', 'label' => 'Notifications'],
                ];
                
                if (\Laravel\Pennant\Feature::active('integrations')) {
                    array_push($links, [
                        'route' => 'organization.settings.integrations', 
                        'label' => 'Integrations', 'feature' => 'integrations'
                    ]);
                }
            @endphp
            
            @foreach($links as $item)
                <flux:navlist.item
                    wire:key="nbi-{{ $item['route'] }}" 
                    :href="route($item['route'])"
                    :current="request()->routeIs($item['route'])"
                    wire:navigate
                    wire:ignore>
                    {{ $item['label'] }}
                </flux:navlist.item>
            @endforeach
        </flux:navlist>
    </div> 
        
    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <div class="mt-5 w-full max-w-2xl space-y-12">
            {{ $slot }}
        </div>
    </div>
</div>
