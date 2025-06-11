@props(['project'])

<x-drop-button 
    href="{{ route('projects.view', $project) }}"
    x-data="{ selected: '{{ $project->id }}' }" 
    x-init="$watch('selected', id => {
        if (id !== '{{ $project->id }}') {
            window.location.href = `/projects/${id}`;
        }
     })">
    <span class="block max-w-[8rem] truncate text-left">
        {{ $project->name }}
    </span>
    <x-slot name="menu">
        <flux:menu>
            <flux:menu.radio.group>
                @foreach ($project->team->projects as $otherProject)
                    <flux:menu.radio
                        :checked="($project->id === $otherProject->id)"
                        :value="$otherProject->id"
                        @click="selected = '{{ $otherProject->id }}'">
                        {{ $otherProject->name }}
                    </flux:menu.radio>
                @endforeach
            </flux:menu.radio.group>
        </flux:menu>
    </x-slot>
</x-drop-button>
