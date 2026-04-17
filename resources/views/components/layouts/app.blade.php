@props([
    'title' => null,
    'breadcrumbs' => null,
    'subheader' => null,
    'hideHeader' => false,
    'showDeviceLinkBlocker' => true,
])

<x-layouts.base
    :title="$title"
    :with-tracking="false"
    theme-color="#ffffff"
    body-classes="min-h-screen">
    <div class="min-h-screen flex flex-col">
        @if(! $hideHeader)
            <flux:header sticky 
                @class([
                    'py-2 backdrop-blur-md',
                    'bg-black text-white',
                    'border-b border-black'
                ])>
                
                <a 
                    href="{{ route('dashboard') }}" 
                    class="ms-2 me-5 flex items-center rtl:space-x-reverse lg:ms-0" 
                    wire:navigate>
                    <x-app-logo />
                </a>
                
                <flux:navbar class="-mb-px text-white [&_*]:text-white">
                    <flux:breadcrumbs class="text-white [&_*]:text-white">
                        @if(auth()->user()->isVerified() && auth()->user()->organizations->count())
                            <flux:breadcrumbs.item separator="slash">
                                <livewire:organization.livewire.organization-dropdown/>
                            </flux:breadcrumbs.item>
                        @endif
                        {{ $breadcrumbs ?? '' }}
                    </flux:breadcrumbs>
                </flux:navbar>
                
                <flux:spacer />
                
                {{-- Temporarily hidden until server inbox UX is finalized. --}}
                {{-- <livewire:account.livewire.server-inbox-menu /> --}}
                
                <div classs="relative z-10 flex items-center content-between">
                    <flux:dropdown position="top" align="end">
                        <flux:profile
                            circle
                            class="cursor-pointer"
                            :initials="auth()->user()->initials()"
                            :chevron="false"
                            avatar:color="slate"
                        />
                        <flux:menu class="text-black">
                            <flux:menu.radio.group>
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                        <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-full">
                                            <span
                                                class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black">
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
                                    {{ __('Account') }}
                                </flux:menu.item>
                                <flux:menu.item href="https://docs.ghostable.dev" target="_blank">
                                    {{ __('Docs') }}
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
                                    {{ __('Sign Out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </flux:header>
        @endif
        
        <div class="flex-1 flex flex-col">
            @if($showDeviceLinkBlocker)
                <livewire:auth.livewire.device-link-blocker />
            @endif
            
            <div class="bg-white">
                {{ $subheader ?? '' }}
            </div>
            
            <div class="bg-white flex-1 flex flex-col">
                <div class="px-2.5 flex-1 flex flex-col">
                    <flux:card id="content-card" class="flex-1 flex flex-col space-y-6 border-zinc-200/80 bg-zinc-50 shadow-none">
                        <flux:main container class="flex-1">
                            {{ $slot }}
                        </flux:main>
                    </flux:card>
                </div>
            </div>
        </div>

        <footer class="bg-white">
            <div class="w-full px-6 lg:px-8 py-2.5 text-sm text-zinc-500 flex items-center justify-between">
                <span>Ghostable LLC © {{ date('Y') }}</span>
                <a href="https://docs.ghostable.dev" target="_blank" class="hover:text-zinc-700">
                    Docs
                </a>
            </div>
        </footer>
    </div>

</x-layouts.base>
