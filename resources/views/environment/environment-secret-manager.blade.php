<x-layouts.environment :environment="$this->environment">
    
    <div class="space-y-6" data-screenshot-ready="env-vars">
        @if($this->pendingPromotionRequestCount > 0)
            <div class="space-y-1">
                <flux:heading size="lg" level="2">{{ __('Variable Promotion Requests') }}</flux:heading>
                <flux:subheading>{{ __('Pending cross-environment variable promotions targeting this environment.') }}</flux:subheading>
            </div>

            <flux:card class="border-zinc-200/80 bg-white shadow-none">
                <livewire:organization.livewire.organization-variable-promotion-requests-manager
                    :project-id="$this->environment->project_id"
                    :target-environment-id="$this->environment->id"
                    :compact="true" />
            </flux:card>
        @endif

        <div data-screenshot-frame="env-vars-table">
            <div class="space-y-1">
                <flux:heading size="lg" level="2">Variables (Zero-Knowledge)</flux:heading>
                <flux:subheading>
                    <div class="max-w-xl">
                        Only ciphertext and metadata are ever stored on Ghostable. This ensures your sensitive values can’t be read, even by us.
                    </div>
                </flux:subheading>
            </div>

            <flux:card class="mt-4 border-zinc-200/80 bg-white shadow-none">
                @if(count($this->variables))
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column 
                                sortable 
                                :sorted="$sortBy === 'name'" 
                                :direction="$sortDirection" 
                                wire:click="sort('name')">Name</flux:table.column>
                            <flux:table.column>Size</flux:table.column>
                            <flux:table.column>Version</flux:table.column>
                            <flux:table.column
                            sortable 
                            :sorted="$sortBy === 'last_updated_at'" 
                            :direction="$sortDirection" 
                            wire:click="sort('last_updated_at')">Last Updated</flux:table.column>
                            <flux:table.column>By</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->variables as $secret)
                                <flux:table.row
                                    wire:key="secret-{{ $secret->id }}"
                                    @class(['opacity-50' => $secret->is_commented])
                                >
                                    <flux:table.cell variant="strong">
                                        <div class="flex items-center gap-2">
                                            {{ $secret->name }}
                                            @if($secret->is_commented)
                                                <flux:badge size="sm" color="slate" icon="hashtag">
                                                    Commented
                                                </flux:badge>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $secret->displayLineBytes }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        v{{ $secret->version }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        {{ $secret->last_updated_at->timezone(timezone())->format(DT_FORMAT)  }}
                                    </flux:table.cell>
                                    <flux:table.cell variant="strong">
                                        {{ $secret->lastUpdatedBy->email }}
                                    </flux:table.cell>
                                    <flux:table.cell align="end" class="flex items-center gap-2 justify-end">
                                        <flux:button
                                            variant="ghost"
                                            icon="information-circle"
                                            class="text-zinc-400 hover:text-zinc-600"
                                            wire:click="viewDetails('{{ $secret->id }}')"
                                            aria-label="View details for {{ $secret->name }}" />
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:callout.heading>No secrets</flux:callout.heading>
                    <flux:callout.text>You haven't created any secrets yet.</flux:callout.text>
                @endif
            </flux:card>
        </div>
    
        <livewire:environment.livewire.environment-secret-activity-feed />
    
        <livewire:environment.livewire.environment-secret-details-viewer />
    
        <livewire:environment.livewire.environment-secret-version-manager />
  
    </div>
    
</x-layouts.environment>
