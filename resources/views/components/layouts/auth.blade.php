@props([
    'title' => $title ?? null,
    'canonical' => $canonical ?? null
])

<x-layouts.guest :title="$title" :canonical="$canonical" :withHeader="false" :withFooter="false" :with-google-tag="false">
    <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-sm flex-col gap-2">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                <span class="flex h-10 w-auto mb-3 items-center justify-center rounded-md">
                    <img class="fill-current text-white" src="{{ asset('images/logo-light.svg') }}"/>
                </span>
                <span class="sr-only">Ghostable</span>
            </a>
            <div class="flex flex-col gap-6 dark">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.guest>
