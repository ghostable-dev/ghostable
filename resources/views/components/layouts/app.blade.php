@props([
    'title' => null,
    'breadcrumbs' => null,
    'subheader' => null    
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        @include('partials.head')
    </head>
    
    <body class="min-h-screen">
        <div class="h-1 w-full block bg-accent"></div>
        <flux:header sticky 
            @class([
                'py-2 backdrop-blur-md',
                'bg-white/20 dark:bg-zinc-900',
                'border-b border-zinc-200 dark:border-white/20'
            ])>
            
            <a 
                href="{{ route('dashboard') }}" 
                class="ms-2 me-5 flex items-center rtl:space-x-reverse lg:ms-0" 
                wire:navigate>
                <x-app-logo />
            </a>
            
            <flux:navbar class="-mb-px">
                <flux:breadcrumbs>
                    @if(auth()->user()->isVerified() && auth()->user()->organizations->count())
                        <flux:breadcrumbs.item separator="slash">
                            <livewire:organization.livewire.organization-dropdown/>
                        </flux:breadcrumbs.item>
                    @endif
                    {{ $breadcrumbs ?? '' }}
                </flux:breadcrumbs>
            </flux:navbar>
            
            <flux:spacer />
            
            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0! max-lg:hidden">
                <flux:navbar.item href="https://docs.ghostable.dev" target="_blank">
                    Docs
                </flux:navbar.item>
                <flux:separator vertical />
            </flux:navbar>
            
            <div classs="relative z-10 flex items-center content-between">
                <flux:dropdown position="top" align="end">
                    <flux:profile
                        circle
                        class="cursor-pointer"
                        :initials="auth()->user()->initials()"
                        :chevron="false"
                        avatar:color="slate"
                    />
                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-full">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('settings.profile')" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item 
                                as="button" 
                                type="submit" 
                                icon="arrow-right-start-on-rectangle" 
                                icon:variant="micro"
                                class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:header>
        
        <div>
            <div class="bg-zinc-50 dark:bg-zinc-800">
                {{ $subheader ?? '' }}
            </div>
            <div>
                <flux:main container>
                    {{ $slot }}
                </flux:main>
            </div>
        </div>
        
        @persist('toast')
            <flux:toast />
        @endpersist
        
        @fluxScripts
        
    </body>
</html>
