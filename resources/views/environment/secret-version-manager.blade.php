<flux:modal variant="flyout" wire:model="showing" class="md:w-6xl">
    <div class="space-y-6">
        <flux:heading size="lg">Change Log</flux:heading>
        <div class="flow-root">
            <flux:table class="min-w-full divide-y divide-gray-300">
                <flux:table.columns>
                    <flux:table.column>Saved At</flux:table.column>
                    <flux:table.column>Version</flux:table.column>
                    <flux:table.column>Size</flux:table.column>
                    <flux:table.column>Changed By</flux:table.column>
                    <flux:table.column>Reason for change</flux:table.column>
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
                                {{ $version->displayLineBytes }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $version->changedBy?->email ?? 'Unknown' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if(! $this->canViewVersionChangeNotes)
                                    <flux:text size="sm" variant="subtle">
                                        Locked
                                    </flux:text>
                                @elseif($version->changeNote)
                                    <div class="space-y-2">
                                        <flux:badge size="sm" color="emerald">
                                            Encrypted change reason saved
                                        </flux:badge>
                                        <flux:text size="sm" variant="subtle">
                                            Stored as client-side ciphertext. Open in a trusted client to decrypt.
                                        </flux:text>
                                        <flux:text size="sm" variant="subtle">
                                            Saved {{ $version->changeNote->created_at?->timezone(timezone())->format(DT_FORMAT) ?? 'Unknown' }}
                                        </flux:text>
                                    </div>
                                @else
                                    <flux:text size="sm" variant="subtle">
                                        No reason saved
                                    </flux:text>
                                @endif
                            </flux:table.cell>
                            {{-- <flux:table.cell>
                                @if($version->id !== $this->secret->latestVersion->id)
                                    <flux:button  
                                        wire:click="restoreToVersion('{{ $version->id }}')">
                                        {{ __('Restore') }}
                                    </flux:button>
                                @endif
                            </flux:table.cell> --}}
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
