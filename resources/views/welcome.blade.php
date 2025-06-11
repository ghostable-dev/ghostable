<x-layouts.guest title="Ghostable">
    <flux:main>
        <div class="py-24 sm:py-32 lg:pb-40">
            
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
          <div class="mx-auto max-w-2xl text-center">
            <div class="flex flex-col items-center gap-2 font-medium">
                <span class="flex h-22 w-22 mb-4 items-center justify-center rounded-md">
                    <x-app-logo-icon class="size-22 fill-current text-black dark:text-white" />
                </span>
            </div>
            <h1 class="text-5xl font-bold tracking-tighter text-balance text-black dark:text-white sm:text-6xl">
                Smart .env Syncing for Laravel Teams
            </h1>
            <p class="mt-8 text-lg font-medium text-pretty text-gray-400 sm:text-xl/8">Ghostable makes it easy to securely manage, share, and validate your environment files—across projects, teams, and pipelines.</p>
            <div class="mt-10 flex items-center justify-center gap-x-4">
                <flux:button 
                    variant="primary" 
                    href="{{ route('login') }}">
                    Get Started
                </flux:button>
                <flux:button 
                    variant="ghost" 
                    target="_blank" 
                    href="https://docs.ghostable.dev">
                    Documentation
                </flux:button>
            </div>
          </div>
        </div>
        
    </div>
    </flux:main>
    
</x-layouts.guest>
