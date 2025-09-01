<div class="mx-auto max-w-7xl px-6 lg:px-8">
    <div class="mx-auto max-w-2xl text-center">
        <div class="flex flex-col items-center gap-2 font-medium">
            <span class="flex h-22 w-22 mb-4 items-center justify-center rounded-md">
                <x-app-logo-icon class="floating size-22 fill-current text-white" />
            </span>
        </div>
        <h1 class="text-5xl font-medium tracking-tighter text-pretty text-balance text-white sm:text-6xl">
            The Complete <span class="bg-gradient-to-r from-brand-light from-10% via-brand via-30% to-brand-light to-90% shadow-xl bg-clip-text text-transparent">.env Suite</span> for Laravel Teams
        </h1>
        <p class="mt-8 text-lg text-gray-400 sm:text-xl/8">
            Securely manage, effortlessly sync, automatically validate, audit changes, and confidently deploy your Laravel environment variables and secrets—all within one unified platform.
        </p>
        <div class="mt-10 flex items-center justify-center gap-x-4">
            <flux:button 
                variant="primary" 
                class="!bg-brand"
                href="{{ route('login') }}">
                Get Started
            </flux:button>
            <flux:button 
                variant="ghost" 
                class="!text-white"
                target="_blank" 
                href="https://docs.ghostable.dev">
                Documentation
            </flux:button>
        </div>
    </div>
</div>