@props([
    'environment',
    'heading' => '',
])

<x-layouts.environment :$environment :$heading>
    
    @php
        $links = [
            ['route' => 'environment.settings.general', 'label' => 'General'],
        ];

        array_push($links, ['route' => 'environment.settings.access', 'label' => 'Access']);
        array_push($links, ['route' => 'environment.settings.notifications', 'label' => 'Notifications']);
        
    @endphp
    
    <div class="flex items-start max-md:flex-col">
        <div class="me-10 w-full pb-4 md:w-[220px]">
            <flux:navlist variant="sidebar">
                @foreach($links as $item)
                    <flux:navlist.item
                        wire:key="nbi-{{ $item['route'] }}" 
                        :href="route($item['route'], $environment->id)" 
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
            <div class="w-full space-y-12">
                 {{ $slot }}
            </div>
        </div>
        
    </div>
   
</x-layouts.environment>
