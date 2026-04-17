{{-- Page Header --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
    @php
        $desktopDeepLink = \App\Support\DesktopDeepLink::forEnvironment($environment);
    @endphp

    {{-- Left: Title + Meta --}}
    <div class="space-y-2 min-w-0">
        
        {{-- Name --}}
        <flux:heading size="xl" level="1" class="!leading-tight truncate font-semibold">
            {{ $environment->project->name }}
            <span class="text-zinc-400">/ {{ $environment->name }}</span>
        </flux:heading>
    </div>
    
    {{-- Right: Meta --}}
    <div class="flex-shrink-0 flex items-center gap-2">
        
        {{-- Type --}}
        <flux:badge variant="soft">
            {{ $environment->type->label() }}
        </flux:badge>

        @if($desktopDeepLink)
            <flux:button
                size="sm"
                variant="ghost"
                href="{{ $desktopDeepLink }}"
                icon:trailing="arrow-top-right-on-square">
                Open in desktop
            </flux:button>
        @endif
        
        {{-- Memebers --}}
        {{-- <flux:avatar.group class="flex-shrink-0">
            @foreach($this->environment->project->organization->users as $user)
                <flux:avatar circle :initials="$user->initials()" />
            @endforeach
        </flux:avatar.group> --}}
        
    </div>

</div>
