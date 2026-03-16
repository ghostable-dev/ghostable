<div class="space-y-6 max-w-4xl" id="key-reshare-requests" data-screenshot-frame="org-key-reshare-queue">

    <x-section>
        <x-slot:title>{{ __('Environment Key Re-share Queue') }}</x-slot:title>
        <x-slot:subheading>
            {{ __('Pending requests for newly linked devices or newly authorized members.') }}
        </x-slot:subheading>

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
            <div class="space-y-3">
                @foreach($this->pendingRequests as $request)
                    <flux:card wire:key="key-reshare-request-{{ $request['id'] }}" class="space-y-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div class="space-y-1">
                                <div class="font-medium">
                                    {{ $request['project_name'] }} / {{ $request['environment_name'] }}
                                </div>
                                <div class="text-zinc-500 text-sm">
                                    <span>{{ $request['created_at'] ?? 'just now' }}</span>
                                    <span class="mx-1" aria-hidden="true">&middot;</span>
                                    <span>Key version {{ $request['required_key_version'] }}</span>
                                </div>
                            </div>
                            
                            <div>
                                @if($request['is_actor'])
                                    <flux:badge color="yellow" size="sm">Action needed</flux:badge>
                                @elseif($request['is_recipient'])
                                    <flux:badge color="blue" size="sm">Waiting</flux:badge>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-1 text-sm">
                            <div>{{ $request['target_user_email'] }}</div>
                            <div class="text-zinc-500">
                                {{ $request['target_device_name'] }}
                                @if(!empty($request['target_device_platform']))
                                    ({{ $request['target_device_platform'] }})
                                @endif
                            </div>
                        </div>

                        @if($request['is_actor'])
                            <div class="space-y-2">
                                <flux:input
                                    label="CLI command"
                                    class="w-full font-mono text-xs"
                                    value="{{ $request['cli_command'] }}"
                                    readonly
                                    copyable/>
                                <div class="flex items-center justify-end">
                                    <a
                                        class="inline-flex items-center rounded-md border border-zinc-200 px-2.5 py-1.5 text-sm hover:bg-zinc-50"
                                        href="{{ $request['desktop_deep_link'] }}">
                                        Open Desktop
                                    </a>
                                </div>
                            </div>
                        @elseif($request['is_recipient'])
                            <div class="text-sm text-zinc-500">
                                Waiting for an environment manager to re-share keys.
                            </div>
                        @endif
                    </flux:card>
                @endforeach
            </div>
        @endif
    </x-section>

</div>
