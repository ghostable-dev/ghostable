<div class="space-y-3 pb-4">
    @foreach($this->pendingInvites as $invite)
        <flux:callout icon="users" variant="ghost" inline>
            <flux:callout.heading>
                Invitation to join <flux:text color="blue">{{ $invite->organization->name }}</flux:text> from {{ $invite->user->email }} ({{ $invite->created_at->timezone(timezone())->diffForHumans() }})
            </flux:callout.heading>
            <x-slot name="actions">
                <flux:button wire:click="accept('{{ $invite->id }}')">Accept</flux:button>
                <flux:button variant="ghost" wire:click="decline('{{ $invite->id }}')">Decline</flux:button>
            </x-slot>
        </flux:callout>
    @endforeach
</div>