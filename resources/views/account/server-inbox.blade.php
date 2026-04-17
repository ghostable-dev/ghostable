<section class="w-full space-y-6" data-screenshot-ready="server-inbox">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">Inbox</flux:heading>
            <flux:subheading size="lg">
                @if($this->organization)
                    Actionable updates for {{ $this->organization->name }}.
                @else
                    No organization is selected.
                @endif
            </flux:subheading>
        </div>

        @if($this->organization)
            <div class="flex items-center gap-2">
                <flux:tabs variant="segmented">
                    <flux:tab wire:click="setFilter('all')" :current="$filter === 'all'">
                        All
                    </flux:tab>
                    <flux:tab wire:click="setFilter('unread')" :current="$filter === 'unread'">
                        Unread
                    </flux:tab>
                </flux:tabs>

                <flux:button
                    variant="ghost"
                    wire:click="markAllAsRead"
                    :disabled="$this->unreadCount === 0">
                    Mark all read
                </flux:button>
            </div>
        @endif
    </div>

    @if(! $this->organization)
        <flux:callout variant="secondary" icon="information-circle">
            <flux:callout.heading>Select an organization to view inbox items.</flux:callout.heading>
        </flux:callout>
    @elseif($this->entries->isEmpty())
        <flux:callout variant="secondary" icon="check-circle">
            <flux:callout.heading>No inbox items.</flux:callout.heading>
            <flux:callout.text>
                New requests and updates will appear here.
            </flux:callout.text>
        </flux:callout>
    @else
        <div class="space-y-3">
            @foreach($this->entries as $entry)
                <flux:card wire:key="server-inbox-entry-{{ $entry['id'] }}" class="space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-zinc-900">
                                    {{ $entry['title'] }}
                                </p>
                                @if($entry['is_unread'])
                                    <flux:badge color="blue" size="sm">Unread</flux:badge>
                                @endif
                                @if($entry['source'] === 'key_reshare')
                                    <flux:badge color="amber" size="sm">Action required</flux:badge>
                                @endif
                            </div>

                            <p class="text-sm text-zinc-700">{{ $entry['description'] }}</p>

                            <p class="text-xs text-zinc-500">
                                {{ $entry['context'] !== '' ? $entry['context'].' · ' : '' }}{{ $entry['created_at_human'] ?? 'just now' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button
                                size="sm"
                                variant="primary"
                                :href="$entry['href']"
                                wire:navigate>
                                Review
                            </flux:button>

                            @if($entry['can_mark_as_read'] && $entry['is_unread'])
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="markAsRead('{{ $entry['source_id'] }}')">
                                    Mark read
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif
</section>
