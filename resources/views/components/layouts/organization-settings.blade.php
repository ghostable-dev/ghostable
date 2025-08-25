<div class="flex items-start max-md:flex-col">
    
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist variant="sidebar">
            @foreach([
                ['route' => 'organization.settings.general', 'label' => 'General'],
                ['route' => 'organization.settings.billing', 'label' => 'Billing'],
                ['route' => 'organization.settings.members', 'label' => 'Members'],
                ['route' => 'organization.settings.notifications', 'label' => 'Notifications'],
            ] as $item)
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
