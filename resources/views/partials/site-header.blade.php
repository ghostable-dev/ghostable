<nav 
    aria-label="Main"
    class="sticky top-0 inset-x-0 z-50 backdrop-blur-sm bg-black/60 shadow-sm">

    <div class="mx-auto max-w-7xl px-6 flex items-center justify-between h-16">
        <!-- Logo (dark only) -->
        <a href="{{ url('/') }}" class="flex items-center">
            <img src="{{ asset('images/logo-light.svg') }}" alt="Ghostable Logo" class="h-7 w-auto">
        </a>

        <!-- Primary nav links -->
        <flux:navbar class="hidden md:flex gap-x-6">
            <flux:navbar.item 
                href="/pricing" 
                class="!text-white">
                Pricing
            </flux:navbar.item>
            <flux:navbar.item  
                href="https://docs.ghostable.dev" 
                target="_blank"
                class="!text-white">
                Docs
            </flux:navbar.item>
            {{-- <flux:navbar.item 
                href="/features"
                class="!text-white">
                Features
            </flux:navbar.item> --}}
        </flux:navbar>

        <!-- Auth buttons -->
        <div class="flex items-center gap-x-4">
            <flux:link 
                variant="ghost" 
                class="!text-white"
                href="{{ route('login') }}">
                Sign in
            </flux:link>
            <flux:button 
                variant="primary" 
                class="bg-white text-black hover:bg-gray-100"
                href="{{ route('register') }}">
                Sign up
            </flux:button>
        </div>
    </div>
</nav>