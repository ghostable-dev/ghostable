<flux:modal variant="flyout" wire:model="showing" class="md:w-2xl">
    <div class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">Details</flux:heading>
            <flux:text variant="subtle">
                Variable metadata and encrypted context captured by trusted clients.
            </flux:text>
        </div>

        <flux:tab.group class="space-y-4">
            <flux:tabs wire:model="tab">
                <flux:tab name="info">Info</flux:tab>
                <flux:tab name="note">Description / Note</flux:tab>
                <flux:tab name="comments">Comments</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="info">
                <div class="flow-root">
                    @if($this->secret)
                        <dl class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach($this->details as $label => $value)
                                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                    <dt class="text-sm/6 font-medium text-gray-900 dark:text-gray-100">{{ $label }}</dt>
                                    <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0 dark:text-gray-400">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="note" class="space-y-3">
                <div class="flex items-center justify-end gap-2">
                    <div class="flex items-center gap-2">
                        @if($this->desktopDeepLink && $this->canViewContext)
                            <flux:button size="sm" variant="ghost" href="{{ $this->desktopDeepLink }}" icon:trailing="arrow-top-right-on-square">
                                Open in desktop
                            </flux:button>
                        @endif

                        @if($this->canEditNote)
                            <flux:badge size="sm" color="blue" icon="lock-closed">Trusted client editing</flux:badge>
                        @elseif($this->canViewContext)
                            <flux:badge size="sm" color="slate" icon="lock-closed">Read only</flux:badge>
                        @endif
                    </div>
                </div>

                @if(! $this->canViewContext)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                        <flux:text size="sm" variant="subtle">
                            You do not have permission to view variable context.
                        </flux:text>
                    </div>
                @elseif($this->secret?->note)
                    <div class="space-y-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge size="sm" color="emerald">Encrypted note stored</flux:badge>
                        </div>
                        <flux:text size="sm" variant="subtle">
                            To view or edit, open in a trusted client.
                        </flux:text>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <flux:text size="sm" variant="subtle">
                                Updated {{ $this->secret->note->updated_at?->timezone(timezone())->format(DT_FORMAT) ?? 'Unknown' }}
                            </flux:text>
                            <flux:text size="sm" variant="subtle">
                                By {{ $this->secret->note->lastUpdatedBy?->email ?? 'Unknown' }}
                            </flux:text>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-200 p-4 dark:border-white/10">
                        <flux:text size="sm" variant="subtle">
                            No note has been saved for this variable.
                        </flux:text>
                    </div>
                @endif
            </flux:tab.panel>

            <flux:tab.panel name="comments" class="space-y-3">
                <div class="flex items-center justify-end gap-2">
                    <div class="flex items-center gap-2">
                        @if($this->desktopDeepLink && $this->canViewContext)
                            <flux:button size="sm" variant="ghost" href="{{ $this->desktopDeepLink }}" icon:trailing="arrow-top-right-on-square">
                                Open in desktop
                            </flux:button>
                        @endif

                        @if($this->canViewContext && ! $this->canComment)
                            <flux:badge size="sm" color="slate" icon="lock-closed">Read only</flux:badge>
                        @endif
                    </div>
                </div>

                @if(! $this->canViewContext)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                        <flux:text size="sm" variant="subtle">
                            Comment history is unavailable without context access.
                        </flux:text>
                    </div>
                @elseif($this->comments->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-200 p-4 dark:border-white/10">
                        <flux:text size="sm" variant="subtle">
                            No comments have been added yet.
                        </flux:text>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($this->comments as $comment)
                            <div class="space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge size="sm" color="emerald">Encrypted comment</flux:badge>
                                    <flux:text size="sm" variant="subtle">
                                        {{ $comment->createdBy?->email ?? 'Unknown' }}
                                    </flux:text>
                                    <flux:text size="sm" variant="subtle">
                                        {{ $comment->created_at?->timezone(timezone())->format(DT_FORMAT) ?? 'Unknown' }}
                                    </flux:text>
                                </div>
                                <flux:text size="sm" variant="subtle">
                                    To view, open in a trusted client.
                                </flux:text>
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:tab.panel>
        </flux:tab.group>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="filled">Close</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
