@props(['environment'])

<x-drop-button 
    href="{{ route('environment.view', $environment) }}"
    x-data="{ selected: '{{ $environment->id }}' }" 
    x-init="$watch('selected', id => {
        if (id !== '{{ $environment->id }}') {
            window.location.href = `/environments/${id}`;
        }
     })">
    <span class="block max-w-[8rem] truncate text-left">
        {{ $environment->name }}
    </span>
    <x-slot name="menu">
        <flux:menu>
            <flux:menu.radio.group>
                @foreach ($environment->project->environments as $otherEnvironment)
                    <flux:menu.radio
                        :checked="($environment->id === $otherEnvironment->id)"
                        :value="$otherEnvironment->id"
                        @click="selected = '{{ $otherEnvironment->id }}'">
                        {{ $otherEnvironment->name }}
                    </flux:menu.radio>
                @endforeach
            </flux:menu.radio.group>
        </flux:menu>
    </x-slot>
</x-drop-button>
