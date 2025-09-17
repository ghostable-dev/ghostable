<x-filament-panels::page>
    <div class="space-y-4">
        <select class="mb-2" wire:model.live="notificationClass">
            @foreach($this->notificationOptions as $class => $label)
                <option value="{{ $class }}">{{ $label }}</option>
            @endforeach
        </select>
        @if ($this->html)
            <iframe 
                height="800px" 
                width="100%" 
                srcdoc="{{ $this->html }}">        
            </iframe>
        @else
            <p class="text-sm text-gray-500">No preview available.</p>
        @endif
    </div>
</x-filament-panels::page>
