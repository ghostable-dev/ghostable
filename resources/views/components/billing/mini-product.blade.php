@props([
    'title',
    'description'
])
<flux:card>
    <div class="md:flex items-center justify-between">
        <div class="max-w-lg mx-auto md:mx-0 mb-10 md:mb-0 flex-grow">
            <h3 class="text-md font-bold leading-6 text-gray-900">
                {{ $title }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
                {{ $description }}
            </p>
        </div>
        <div class="text-center">
            {{ $slot }}
        </div>
    </div>
</flux:card>