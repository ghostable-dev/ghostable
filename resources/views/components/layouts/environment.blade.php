@props([
    'environment',
    'heading' => ''    
])
<div class="space-y-6">
    
    <x-slot name="breadcrumbs">
        <flux:breadcrumbs.item separator="slash">
            <x-projects-drop-button :project="$this->environment->project"/>
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>
            <x-environments-drop-button :environment="$this->environment"/>
        </flux:breadcrumbs.item>
    </x-slot>
    
    @include('environment.header')
    
    <flux:navbar>
        @foreach([
            ['route' => 'environment.variables', 'label' => 'Variables'],
            ['route' => 'environment.validation', 'label' => 'Validation'],
            ['route' => 'environment.secrets', 'label' => 'Secrets'],
            ['route' => 'environment.settings', 'label' => 'Settings'],
            ['route' => 'environment.access', 'label' => 'Access'],
            ['route' => 'environment.notifications', 'label' => 'Notifications'],
            ['route' => 'environment.activity', 'label' => 'Activity'], 
        ] as $item)
            <flux:navbar.item
                wire:key="nbi-{{ $item['route'] }}" 
                :href="route($item['route'], $environment->id)" 
                :current="request()->routeIs($item['route'])"
                wire:navigate>
                {{ $item['label'] }}
            </flux:navbar.item>
        @endforeach
    </flux:navbar>

    <div class="space-y-6">
        {{ $slot }}
    </div>
</div>
