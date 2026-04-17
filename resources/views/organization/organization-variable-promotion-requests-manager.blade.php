@php
    $isCompact = (bool) ($this->compact ?? false);
    $pendingRequests = $this->pendingRequests;
@endphp

<div class="{{ $isCompact ? 'space-y-3' : 'space-y-6 max-w-4xl' }}" id="variable-promotion-requests">
    @if($isCompact)
        @if($pendingRequests->isEmpty())
            <flux:callout icon="check-circle" variant="subtle">
                <flux:callout.heading>No pending promotion requests.</flux:callout.heading>
                <flux:callout.text>
                    Requests created from desktop variable promotion will appear here until approved or rejected.
                </flux:callout.text>
            </flux:callout>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Scope</flux:table.column>
                    <flux:table.column>Requester</flux:table.column>
                    <flux:table.column>Keys</flux:table.column>
                    <flux:table.column>Requested</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($pendingRequests as $request)
                        @php
                            $entryNames = collect($request['entry_names'] ?? [])
                                ->map(fn ($name) => trim((string) $name))
                                ->filter(fn ($name) => $name !== '')
                                ->values();
                            $entryNamePreview = $entryNames->take(3)->implode(', ');
                            $remainingEntryNames = max(0, $entryNames->count() - 3);
                        @endphp

                        <flux:table.row wire:key="compact-variable-promotion-request-{{ $request['id'] }}">
                            <flux:table.cell>
                                <div class="font-medium text-sm">
                                    {{ $request['project_name'] }}
                                </div>
                                <div class="text-sm text-zinc-500">
                                    {{ $request['source_environment_name'] }} → {{ $request['target_environment_name'] }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm">
                                    {{ $request['requested_by_email'] }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm text-zinc-600">
                                    @if($entryNamePreview !== '')
                                        {{ $entryNamePreview }}
                                        @if($remainingEntryNames > 0)
                                            <span class="text-zinc-500">+{{ $remainingEntryNames }} more</span>
                                        @endif
                                    @else
                                        {{ $request['entry_count'] }} variable{{ $request['entry_count'] === 1 ? '' : 's' }}
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm text-zinc-500">
                                    {{ $request['created_at'] ?? 'just now' }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($request['is_actor'])
                                    <flux:badge color="yellow" size="sm">Action needed</flux:badge>
                                @elseif($request['is_requester'])
                                    <flux:badge color="blue" size="sm">Waiting</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                @if(!empty($request['desktop_deep_link']))
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        href="{{ $request['desktop_deep_link'] }}"
                                        icon:trailing="arrow-top-right-on-square">
                                        Review in desktop
                                    </flux:button>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @else
        <x-section>
            <x-slot:title>{{ __('Variable Promotion Requests') }}</x-slot:title>
            <x-slot:subheading>
                {{ __('Pending cross-environment variable promotions that require approval.') }}
            </x-slot:subheading>

            @if($pendingRequests->isEmpty())
                <flux:callout icon="check-circle" variant="subtle">
                    <flux:callout.heading>No pending promotion requests.</flux:callout.heading>
                    <flux:callout.text>
                        Requests created from desktop variable promotion will appear here until approved or rejected.
                    </flux:callout.text>
                </flux:callout>
            @else
                <div class="space-y-3">
                    @foreach($pendingRequests as $request)
                        <flux:card wire:key="variable-promotion-request-{{ $request['id'] }}" class="space-y-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div class="space-y-1">
                                    <div class="font-medium">
                                        {{ $request['project_name'] }}
                                    </div>
                                    <div class="text-zinc-500 text-sm">
                                        <span>{{ $request['source_environment_name'] }}</span>
                                        <span class="mx-1" aria-hidden="true">→</span>
                                        <span>{{ $request['target_environment_name'] }}</span>
                                    </div>
                                </div>

                                <div>
                                    @if($request['is_actor'])
                                        <flux:badge color="yellow" size="sm">Approval needed</flux:badge>
                                    @elseif($request['is_requester'])
                                        <flux:badge color="blue" size="sm">Waiting</flux:badge>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-1 text-sm text-zinc-600">
                                <div>
                                    Requested by {{ $request['requested_by_email'] }}
                                    @if(!empty($request['created_at']))
                                        <span class="mx-1" aria-hidden="true">&middot;</span>
                                        <span>{{ $request['created_at'] }}</span>
                                    @endif
                                </div>
                                <div>
                                    {{ $request['entry_count'] }} variable{{ $request['entry_count'] === 1 ? '' : 's' }}
                                    @if($request['includes_values'])
                                        with encrypted values
                                    @else
                                        as empty placeholders
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                @if(!empty($request['desktop_deep_link']))
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        href="{{ $request['desktop_deep_link'] }}"
                                        icon:trailing="arrow-top-right-on-square">
                                        Open in desktop
                                    </flux:button>
                                @endif

                                @if($request['is_actor'])
                                    <flux:button
                                        size="sm"
                                        variant="filled"
                                        wire:click="approveRequest('{{ $request['id'] }}')">
                                        Approve
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="promptRejectRequest('{{ $request['id'] }}')">
                                        Reject
                                    </flux:button>
                                @endif
                            </div>
                        </flux:card>
                    @endforeach
                </div>
            @endif
        </x-section>
    @endif

    <flux:modal name="reject-promotion-request" class="md:w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">Reject Promotion Request</flux:heading>

            <flux:text>
                This will mark the request as rejected and notify the requester.
            </flux:text>

            <flux:textarea
                label="Reason (optional)"
                placeholder="Explain why this promotion is being rejected."
                wire:model="rejectReason"/>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="rejectRequest">
                    Reject Request
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>
