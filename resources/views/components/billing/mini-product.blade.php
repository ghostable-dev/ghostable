@props([
    'title',
    'description',
    'features' => [],
    'highlight' => false
])
<flux:card class="{{ $highlight ? 'ring-2 ring-emerald-300 bg-emerald-50/40' : '' }}">
    <div class="md:flex items-center justify-between">
        <div class="max-w-lg mx-auto md:mx-0 mb-10 md:mb-0 flex-grow">
            <h3 class="text-md font-bold leading-6 {{ $highlight ? 'text-emerald-900' : 'text-gray-900' }}">
                {{ $title }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                {{ $description }}
            </p>
            @if(count($features))
                <ul role="list" class="mt-4 space-y-2 text-sm/6 text-gray-600">
                    @foreach($features as $feature)
                        <li class="flex gap-x-2">
                            <flux:icon.check-circle variant="micro"/>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="text-center">
            {{ $slot }}
        </div>
    </div>
</flux:card>
