@props(['title'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-accent">
    
    <x-head :dark-mode="false" :title="$title ?? null" tracking/>
    <body class="min-h-screen antialiased">
        
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
        @fluxScripts
    </body>
</html>
