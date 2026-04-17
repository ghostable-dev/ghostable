@if($this->organization)
    <flux:dropdown position="top" align="end" class="max-lg:hidden">
        <flux:button variant="ghost" class="relative text-white">
            <flux:icon.bell variant="solid" />
            @if($this->unreadCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-blue-500 px-1 text-[10px] font-semibold text-white ring-2 ring-black">
                    {{ $this->unreadBadgeLabel }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-[26rem] text-black">
            <div class="flex items-center justify-between gap-2 px-3 py-2">
                <div>
                    <p class="text-sm font-semibold text-zinc-900">Inbox</p>
                    <p class="text-xs text-zinc-500">
                        {{ $this->unreadCount }} unread
                    </p>
                </div>
                <flux:link :href="route('inbox')" wire:navigate>
                    View all
                </flux:link>
            </div>

            <flux:menu.separator />

            @forelse($this->entries as $entry)
                <flux:menu.item
                    wire:key="header-inbox-entry-{{ $entry['id'] }}"
                    :href="$entry['href']"
                    wire:navigate>
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-xs font-semibold text-zinc-900">{{ $entry['title'] }}</span>
                            @if($entry['is_unread'])
                                <flux:badge size="sm" color="blue">Unread</flux:badge>
                            @endif
                        </div>
                        <span class="truncate text-xs text-zinc-600">{{ $entry['description'] }}</span>
                        <span class="truncate text-[11px] text-zinc-500">
                            {{ $entry['context'] !== '' ? $entry['context'].' · ' : '' }}{{ $entry['created_at_human'] ?? 'just now' }}
                        </span>
                    </div>
                </flux:menu.item>
            @empty
                <div class="px-3 py-3 text-xs text-zinc-500">
                    No inbox items yet.
                </div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
@endif
