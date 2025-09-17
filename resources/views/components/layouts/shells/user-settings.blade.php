<x-layouts.app>
    @include('partials.settings-heading')
        
    <div class="flex items-start max-md:flex-col">
        <div class="me-10 w-full pb-4 md:w-[220px]">
            <flux:navlist variant="sidebar">
            @foreach([
                ['route' => 'settings.profile', 'label' => 'Profile'],
                ['route' => 'settings.password', 'label' => 'Password'],
                ['route' => 'settings.notifications', 'label' => 'Notifications'],
                ['route' => 'settings.appearance', 'label' => 'Appearance'],
                ['route' => 'settings.two-factor', 'label' => 'Security'],
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
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
