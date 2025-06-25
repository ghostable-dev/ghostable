<header 
    x-data="{ show: false }"
    x-init="window.addEventListener('scroll', () => show = window.scrollY > 50)"
    x-bind:class="show ? 'opacity-100 backdrop-blur-sm' : 'opacity-0 pointer-events-none'"
    class="fixed top-0 inset-x-0 z-50 transition-opacity duration-500 bg-white/60 dark:bg-black/60 shadow-sm">
  
    <div class="mx-auto max-w-7xl px-6 flex items-center justify-between">
        <a href="{{ url('/') }}">
            {{-- <x-app-logo-icon class="size-5 fill-current text-black dark:text-white" /> --}}
            <img src="{{ asset('images/logo-dark.svg') }}" alt="Ghostable Logo" class="h-7 w-auto block dark:hidden">
            <img src="{{ asset('images/logo-light.svg') }}" alt="Ghostable Logo" class="h-7 w-auto hidden dark:block">
        </a>
        
        <flux:navbar class="hidden md:flex gap-x-4">
            <flux:navbar.item 
                href="/pricing"
                class="!text-black dark:!text-white">
                Pricing
            </flux:navbar.item>
            <flux:navbar.item  
                href="https://docs.ghostable.dev"
                class="!text-black dark:!text-white"
                target="_blank">
                Docs
            </flux:navbar.item>
            <flux:navbar.item 
                href="/features"
                class="!text-black dark:!text-white">
                Features
            </flux:navbar.item>
        </flux:navbar>
        
        <div class="flex items-center gap-x-4">
            <flux:link 
                variant="ghost"
                href="{{ route('login') }}">
                Sign in
            </flux:link>
            <flux:button 
                variant="primary"
                href="{{ route('register') }}">
                Sign up
            </flux:button>
        </div>

    </div>
</header>