<flux:modal variant="flyout" wire:model="showing" class="md:w-6xl">
    <div class="space-y-6">
        <flux:heading size="lg">Versions</flux:heading>
        <div class="flow-root">
            <flux:table class="min-w-full divide-y divide-gray-300">
                <flux:table.columns>
                    <flux:table.column>Saved At</flux:table.column>
                    <flux:table.column>Version</flux:table.column>
                    <flux:table.column>Value</flux:table.column>
                    <flux:table.column>Changed By</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->versions as $version)
                        <flux:table.row wire:key="version-{{ $version->id }}">
                            <flux:table.cell>
                                {{ $version->created_at->timezone(timezone())->format(DT_FORMAT) }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $version->version }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $secret = $version->isSecret();
                                    $showingSecret = $this->showingValues[$version->id] ?? null;
                                @endphp
                                <flux:input 
                                    value="{{ $showingSecret ? $version->value : $version->displayValue() }}"
                                    readonly>
                                    @if($secret)
                                        <x-slot name="iconTrailing">
                                            <flux:button 
                                                wire:click="toggleSecret('{{ $version->id }}')" 
                                                size="sm" 
                                                variant="subtle" 
                                                icon="{{ !$showingSecret ? 'eye' : 'eye-slash'}}" 
                                                class="-mr-1" />
                                        </x-slot>
                                    @endif
                                </flux:input>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $version->changedBy->email }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($version->id !== $this->variable->latestVersion->id)
                                    <flux:button  
                                        wire:click="restoreToVersion('{{ $version->id }}')">
                                        {{ __('Restore') }}
                                    </flux:button>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
        <div class="flex gap-2 justify-end">
            <flux:modal.close>
                <flux:button variant="filled">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>