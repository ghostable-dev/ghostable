<flux:dropdown>
    <flux:button variant="ghost">
        <flux:icon.bell variant="solid"/>
        <span
        @class(['absolute block rounded-full size-2 ring-2',
        'top-2 right-4',
        'bg-green-400 ring-white'])></span>
    </flux:button>
    <flux:menu>
        @foreach(auth()->user()->pendingInvites() as $pendingInvite)
        Items
        @endforeach
            
    </flux:menu>
</flux:dropdown>
    
{{-- <x-drop-button href="#">
     <flux:icon.bell class="text-zinc-900"/>
        <span
        @class(['absolute block rounded-full size-2 ring-2',
        'top-1.5 right-1.5',
        'bg-green-400 ring-white'])></span>  
    <x-slot name="menu">
        <flux:menu>
            Items
        </flux:menu>
    </x-slot>
</x-drop-button> --}}
