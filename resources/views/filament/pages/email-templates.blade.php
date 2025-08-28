<x-filament-panels::page>
    <div class="space-y-4">
        <select wire:model.live="notificationClass" class="fi-select block w-full max-w-md rounded-lg border-gray-300 dark:bg-gray-900">
            @foreach($this->notificationOptions as $class => $label)
                <option value="{{ $class }}">{{ $label }}</option>
            @endforeach
        </select>

        @if ($this->html)
            <div class="border rounded-lg bg-white p-4 dark:bg-gray-800">
                {!! $this->html !!}
            </div>
        @else
            <p class="text-sm text-gray-500">No preview available.</p>
        @endif
    </div>
</x-filament-panels::page>
