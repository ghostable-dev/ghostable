<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ $this->environment->name }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your environment variables.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    
    
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Key</flux:table.column>
            <flux:table.column>Value</flux:table.column>
            <flux:table.column>Last Updated</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->environment->variables as $var)
                <flux:table.row>
                    <flux:table.cell>
                        {{ $var->key }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="inline-block min-w-xs max-w-xs">
                        @if ($showing[$var->id] ?? false)
                            {{ $var->value }}
                        @else
                            ••••••••••••••
                        @endif
                    </span>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($var->updated_at->greaterThan(now()->subDay()))
                            {{ $var->updated_at->diffForHumans() }}
                        @else
                            {{ $var->updated_at->format('M j, Y \a\t g:i A') }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" wire:click="edit('{{ $var->id }}')">Edit</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="toggleShow('{{ $var->id }}')">
                            {{ $showing[$var->id] ?? false ? 'Hide' : 'Show' }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    
    
</section>