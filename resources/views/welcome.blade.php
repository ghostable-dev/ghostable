<x-layouts.guest title="Ghostable">
    <div class="bg-gradient-to-br from-teal-200 to-teal-600 flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ auth()->check() ? route('login') : url('/dashboard') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-22 w-22 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-22 fill-current text-white dark:text-white" />
                    </span>
                </a>
            </div>
        </div>
</x-layouts.guest>
