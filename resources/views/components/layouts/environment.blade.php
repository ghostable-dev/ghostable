@props([
    'environment',
    'heading' => ''    
])

<x-slot name="breadcrumbs">
    <flux:breadcrumbs.item separator="slash">
        <x-projects-drop-button :project="$this->environment->project"/>
    </flux:breadcrumbs.item>
    <flux:breadcrumbs.item>
        <x-environments-drop-button :environment="$this->environment"/>
    </flux:breadcrumbs.item>
</x-slot>
        
<x-slot name="subheader">
    <div class="w-full block border-b pt-6">
        <div class="mx-auto w-full [:where(&)]:max-w-7xl px-6 lg:px-8">
            
            @include('environment.header')
            
            <flux:navbar>
                @foreach([
                    ['route' => 'environment.variables', 'label' => 'Variables', 'current' => null],
                    //['route' => 'environment.validation', 'label' => 'Validation', 'current' => null],
                    ['route' => 'environment.secrets', 'label' => 'Secrets', 'current' => null],
                    ['route' => 'environment.activity', 'label' => 'Activity', 'current' => null], 
                    ['route' => 'environment.settings.general', 'label' => 'Settings', 'current' => request()->routeIs('environment.settings.*')],
                ] as $item)
                    <flux:navbar.item
                        wire:key="nbi-{{ $item['route'] }}" 
                        :href="route($item['route'], $environment->id)" 
                        :current="is_null($item['current']) ? request()->routeIs($item['route']) : $item['current']"
                        wire:navigate>
                        {{ $item['label'] }}
                    </flux:navbar.item>
                @endforeach
            </flux:navbar>
        </div>
    </div>
</x-slot>
    
<div>
    <div class="space-y-6">
        {{ $slot }}
    </div>
</div>
