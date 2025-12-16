<nav 
    aria-label="Main"
    class="sticky top-0 inset-x-0 z-50 backdrop-blur-sm bg-black/80 shadow-sm">

    <div class="mx-auto max-w-7xl px-6 flex items-center justify-between h-16">
        
        <!-- Logo -->
        <a href="{{ url('/') }}" class="flex items-center">
            <img src="{{ asset('images/logo-light.svg') }}" alt="Ghostable Logo" class="h-7 w-auto">
        </a>
        
        @php
            $resourceLinks = [
                ['label' => 'Integrations', 'href' => route('integrations.index'), 'target' => '_self', 'current' => request()->route()->named('integrations.*')],
                ['label' => 'Learning', 'href' => route('learn.index'), 'target' => '_self', 'current' => request()->route()->named('learn.*')],
                ['label' => 'Blog', 'href' => route('blog.index'), 'target' => '_self', 'current' => request()->route()->named('blog.*')],
            ];

            $links = [
                ['label' => 'Docs', 'href' => 'https://docs.ghostable.dev', 'target' => '_blank', 'current' => false]
            ];
        @endphp

        <!-- Primary nav links -->
        <flux:navbar class="hidden md:flex items-center gap-x-6 dark">
            <flux:navbar.item
                :current="request()->route()->named('pricing')"
                href="{{ route('pricing') }}"
                target="_self">
                Pricing
            </flux:navbar.item>

            @foreach($links as $link)
                <flux:navbar.item
                    :current="$link['current']"
                    href="{{ $link['href'] }}"
                    target="{{ $link['target'] }}">
                    {{ $link['label'] }}
                </flux:navbar.item>
            @endforeach

            <flux:dropdown>
                <flux:button variant="ghost" class="!text-white" icon:trailing="chevron-down">
                    Resources
                </flux:button>
                <flux:menu>
                    @foreach($resourceLinks as $link)
                        <flux:menu.item
                            :current="$link['current']"
                            href="{{ $link['href'] }}"
                            target="{{ $link['target'] }}">
                            {{ $link['label'] }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

        </flux:navbar>

        <!-- Auth buttons & mobile navigation -->
        <div class="flex items-center gap-x-4">
            
            <!-- Mobile nav toggle -->
            <flux:dropdown class="md:hidden">
                <flux:button
                    icon="bars-3"
                    variant="ghost"
                    class="!text-white"
                />
                <flux:menu>
                    <flux:menu.item
                        :current="request()->route()->named('pricing')"
                        href="{{ route('pricing') }}"
                        target="_self">
                        Pricing
                    </flux:menu.item>
                    @foreach($links as $link)
                        <flux:menu.item 
                            :current="$link['current']"
                            href="{{ $link['href'] }}"
                            target="{{ $link['target'] }}">
                            {{ $link['label'] }}
                        </flux:menu.item>
                    @endforeach
                    @foreach($resourceLinks as $link)
                        <flux:menu.item
                            :current="$link['current']"
                            href="{{ $link['href'] }}"
                            target="{{ $link['target'] }}">
                            {{ $link['label'] }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

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
