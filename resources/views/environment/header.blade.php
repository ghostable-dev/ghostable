{{-- Page Header --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
    {{-- Left: Title + Meta --}}
    <div class="space-y-2 min-w-0">
        <flux:heading size="xl" level="1" class="!leading-tight truncate font-semibold">
            {{ $environment->project->name }}
            <span class="text-zinc-400">/ {{ $environment->name }}</span>
        </flux:heading>

        <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-500">
            <flux:badge size="sm" variant="soft">
                {{ $environment->type->label() }}
            </flux:badge>

            @if ($environment->base)
                <div class="flex items-center gap-1">
                    <flux:icon.git-branch variant="micro"/>
                    <flux:text>
                        Derived from
                        <span class="text-brand font-semibold">{{ $environment->base->name }}</span>
                    </flux:text>
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Avatars --}}
    {{-- <flux:avatar.group class="flex-shrink-0">
        @foreach($this->environment->project->team->users as $user)
            <flux:avatar circle :initials="$user->initials()" />
        @endforeach
    </flux:avatar.group> --}}
</div>