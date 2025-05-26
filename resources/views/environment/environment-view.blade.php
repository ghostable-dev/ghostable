<section class="w-full space-y-4">
    
    @include('environment.partials.environment-breadcrumbs')
    
    <div class="relative w-full">
        <flux:heading size="xl" level="1">
            {{ $this->environment->project->name }} • <span class="text-gray-400">{{ $this->environment->name }}</span>
        </flux:heading>
        <flux:subheading class="mb-6">
            {{ __('Manage your environment variables.') }}
        </flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    
    <flux:avatar.group class="mt-6">
        @foreach($this->environment->project->team->users as $user)
            <flux:avatar circle size="xs" :initials="$user->initials()" />
        @endforeach
    </flux:avatar.group>
    
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
                        <flux:text>{{ $var->key }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($editing === $var->id)
                            <flux:input variant="filled" wire:model.defer="editedValues.{{ $var->id }}" />
                        @else
                            <flux:input value="{{ $var->value }}" readonly copyable />
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($var->updated_at->greaterThan(now()->subDay()))
                            {{ $var->updated_at->diffForHumans() }}
                        @else
                            {{ $var->updated_at->format('M j, Y \a\t g:i A') }}
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($editing === $var->id)
                            <flux:button size="sm" wire:click="save('{{ $var->id }}')">Save</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="cancelEdit">Cancel</flux:button>
                        @else
                            <flux:button size="sm" wire:click="edit('{{ $var->id }}')">Edit</flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    
</section>