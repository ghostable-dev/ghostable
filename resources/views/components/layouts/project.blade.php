@props([
    'project',
    'heading' => ''    
])

<x-slot name="breadcrumbs">
    <flux:breadcrumbs.item separator="slash">
        <x-projects-drop-button :project="$project"/>
    </flux:breadcrumbs.item>
</x-slot>
        
<x-slot name="subheader">
    <div class="w-full block pt-2">
        <div class="w-full px-6 lg:px-8">
            <flux:navbar>
                @foreach([
                    ['route' => 'project.environments', 'label' => 'Environments', 'current' => null],
                    ['route' => 'project.activity', 'label' => 'Activity', 'current' => null], 
                    ['route' => 'project.settings.general', 'label' => 'Settings', 'current' => request()->routeIs('project.settings.*')],
                ] as $item)
                    <flux:navbar.item
                        wire:key="nbi-{{ $item['route'] }}" 
                        :href="route($item['route'], $project->id)" 
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
