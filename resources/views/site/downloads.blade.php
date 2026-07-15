@php
    $stableVersion = config('desktop-updates.channels.stable.short_version');
    $platforms = [
        [
            'name' => 'macOS',
            'icon' => 'apple',
            'description' => 'For Apple silicon and Intel Macs.',
            'format' => 'Universal DMG',
            'requirements' => 'macOS 13 or later',
            'url' => route('desktop.download'),
        ],
        [
            'name' => 'Windows',
            'icon' => 'computer-desktop',
            'description' => 'For modern 64-bit Windows PCs.',
            'format' => 'x64 installer',
            'requirements' => 'Windows 10 or later',
            'url' => config('desktop-updates.downloads.windows'),
        ],
        [
            'name' => 'Linux',
            'icon' => 'command-line',
            'description' => 'For common 64-bit Linux distributions.',
            'format' => 'x64 AppImage',
            'requirements' => 'Ubuntu, Debian, Fedora, and compatible distributions',
            'url' => config('desktop-updates.downloads.linux'),
        ],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="Download Ghostable Desktop"
        description="Download the Ghostable V3 desktop app for macOS, Windows, or Linux. Choose the Electron build for your operating system."
        :keywords="['Ghostable download', 'Ghostable Desktop', 'Electron environment manager']"
    />
@endpush

<x-layouts.guest title="Download Ghostable Desktop" canonical="{{ route('download') }}" :show-promo-banner="false">
    <section class="relative isolate overflow-hidden bg-zinc-950 px-6 pb-20 pt-20 text-white lg:px-8 lg:pb-28 lg:pt-28">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(70,185,168,0.2),transparent_40%)]"></div>
        <div class="mx-auto max-w-4xl text-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-brand/30 bg-brand/10 px-3 py-1.5 text-sm font-medium text-brand-light">
                <span class="size-1.5 rounded-full bg-brand"></span>
                Ghostable V3 Desktop
            </div>
            <h1 class="mt-6 text-5xl font-medium tracking-[-0.055em] text-balance sm:text-6xl lg:text-7xl">
                Download Ghostable.
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg/8 text-zinc-300 sm:text-xl/8">
                Choose the Electron build for your operating system. Your environments stay Git-controlled and encryption happens on your device.
            </p>
            @if($stableVersion)
                <p class="mt-5 font-mono text-sm text-zinc-500">Latest stable · v{{ $stableVersion }}</p>
            @endif
        </div>
    </section>

    <section class="bg-zinc-100 px-6 py-20 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="grid items-stretch gap-6 lg:grid-cols-3">
                @foreach($platforms as $platform)
                    <div class="flex flex-col rounded-3xl border border-zinc-200 bg-white p-7 shadow-sm sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex size-12 items-center justify-center rounded-2xl bg-zinc-950 text-brand">
                                <flux:icon :name="$platform['icon']" class="size-6" />
                            </div>
                            @if($platform['url'])
                                <flux:badge color="teal" size="sm">Available</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Coming soon</flux:badge>
                            @endif
                        </div>

                        <h2 class="mt-7 text-2xl font-semibold tracking-tight text-zinc-950">{{ $platform['name'] }}</h2>
                        <p class="mt-3 min-h-14 text-base/7 text-zinc-600">{{ $platform['description'] }}</p>

                        <dl class="mt-6 space-y-3 border-t border-zinc-200 pt-6 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-zinc-500">Package</dt>
                                <dd class="text-right font-medium text-zinc-900">{{ $platform['format'] }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-zinc-500">Supports</dt>
                                <dd class="max-w-48 text-right font-medium text-zinc-900">{{ $platform['requirements'] }}</dd>
                            </div>
                        </dl>

                        <div class="mt-auto pt-8">
                            @if($platform['url'])
                                <flux:button
                                    href="{{ $platform['url'] }}"
                                    variant="primary"
                                    icon="arrow-down-tray"
                                    class="w-full justify-center">
                                    Download for {{ $platform['name'] }}
                                </flux:button>
                            @else
                                <flux:button disabled variant="filled" class="w-full justify-center">
                                    {{ $platform['name'] }} coming soon
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 grid gap-5 rounded-2xl border border-zinc-200 bg-white p-6 sm:grid-cols-2 sm:p-8">
                <div class="flex gap-4">
                    <flux:icon.shield-check class="mt-0.5 size-6 shrink-0 text-brand-dark" />
                    <div>
                        <h2 class="font-semibold text-zinc-950">Private by construction</h2>
                        <p class="mt-2 text-sm/6 text-zinc-600">Encryption and decryption happen in the trusted desktop client. Ghostable does not need your plaintext environment values.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <flux:icon.arrow-path class="mt-0.5 size-6 shrink-0 text-brand-dark" />
                    <div>
                        <h2 class="font-semibold text-zinc-950">Updates included</h2>
                        <p class="mt-2 text-sm/6 text-zinc-600">A Ghostable license includes one year of desktop updates. <flux:link href="{{ route('pricing') }}">Compare licenses</flux:link>.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.guest>
