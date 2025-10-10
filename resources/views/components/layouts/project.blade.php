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
    
   @if ($project->is_legacy)
        <div class="flex items-center bg-indigo-600 px-6 py-2.5 sm:px-3.5">
            <p class="mx-auto text-sm font-medium text-white text-center">
                <span class="inline-flex items-center gap-1.5">
                    <span>This project uses the <strong>legacy variable storage</strong>.</span>
                </span>
                <span class="block sm:inline ml-2">
                    Consider upgrading to the latest version that uses <strong class="underline">Zero-Knowledge</strong> storage.
                </span>
            </p>
        </div>
    @else
        <div class="flex items-center bg-emerald-600 px-6 py-2.5 sm:px-3.5">
            <p class="mx-auto text-sm font-medium text-white text-center">
                <span class="inline-flex items-center gap-1.5">
                    <span>This project uses the latest <strong>Zero-Knowledge</strong> storage.</span>
                </span>
            </p>
        </div>
    @endif

    <div class="w-full block border-b pt-6">
        <div class="mx-auto w-full [:where(&)]:max-w-7xl px-6 lg:px-8">
            
            @include('project.partials.page-header')
            
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
