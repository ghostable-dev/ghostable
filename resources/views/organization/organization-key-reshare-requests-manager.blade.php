<div class="space-y-6 w-full" id="key-reshare-requests" data-screenshot-frame="org-key-reshare-queue">

    @if (! $this->guidedFlowEnabled)
        <flux:callout icon="information-circle" variant="subtle">
            <flux:callout.heading>Guided key re-share is disabled.</flux:callout.heading>
            <flux:callout.text>
                Enable the <code>guided_key_reshare_v2</code> organization feature to use this queue.
            </flux:callout.text>
        </flux:callout>
    @elseif($this->pendingRequests->isEmpty())
        <flux:callout icon="check-circle" variant="subtle">
            <flux:callout.heading>No pending key re-share requests.</flux:callout.heading>
            <flux:callout.text>
                New devices and authorized members will appear here when key access is pending.
            </flux:callout.text>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Scope</flux:table.column>
                <flux:table.column>Recipient</flux:table.column>
                <flux:table.column>Device</flux:table.column>
                <flux:table.column>Requested</flux:table.column>
                <flux:table.column>Key</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->pendingRequests as $request)
                    <flux:table.row wire:key="key-reshare-request-{{ $request['id'] }}">
                        <flux:table.cell>
                            <div class="font-medium text-sm">
                                {{ $request['project_name'] }} / {{ $request['environment_name'] }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">
                                {{ $request['target_user_email'] }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm text-zinc-600">
                                {{ $request['target_device_name'] }}
                                @if(!empty($request['target_device_platform']))
                                    ({{ $request['target_device_platform'] }})
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm text-zinc-500">
                                {{ $request['created_at'] ?? 'just now' }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm text-zinc-500">
                                v{{ $request['required_key_version'] }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($request['is_actor'])
                                <flux:badge color="yellow" size="sm">Action needed</flux:badge>
                            @elseif($request['is_recipient'])
                                <flux:badge color="blue" size="sm">Waiting</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if($request['is_actor'])
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        href="{{ $request['desktop_deep_link'] }}"
                                        icon:trailing="arrow-top-right-on-square">
                                        Open in desktop
                                    </flux:button>
                                </div>
                            @elseif($request['is_recipient'])
                                <span class="text-xs text-zinc-500">
                                    Waiting for manager
                                </span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

</div>
