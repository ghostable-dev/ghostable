<x-layouts.app :title="__('Local Audit Webhook Inbox')">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Local Audit Webhook Inbox') }}</flux:heading>
                <flux:subheading>
                    {{ __('Inspect captured local webhook requests while testing audit stream delivery.') }}
                </flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge color="slate">{{ __('Driver:') }} {{ $driver }}</flux:badge>
                @if($driver === 'database')
                    <form method="POST" action="{{ route('local.audit-webhooks.clear') }}">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="ghost" type="submit">{{ __('Clear captures') }}</flux:button>
                    </form>
                @endif
            </div>
        </div>

        @if(session('status'))
            <flux:callout color="green" icon="check-circle">
                <flux:callout.heading>{{ session('status') }}</flux:callout.heading>
            </flux:callout>
        @endif

        @if($driver !== 'database')
            <flux:callout color="slate" icon="information-circle">
                <flux:callout.heading>{{ __('Database capture is disabled') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Set AUDIT_WEBHOOK_RECEIVER_DRIVER=database to store captures in this inbox.') }}
                    {{ __('Use AUDIT_WEBHOOK_RECEIVER_DRIVER=log to inspect payloads in logs instead.') }}
                </flux:callout.text>
            </flux:callout>
        @endif

        @if($driver === 'database' && ! $hasCaptureTable)
            <flux:callout color="yellow" icon="information-circle">
                <flux:callout.heading>{{ __('Capture table not installed') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Run `php artisan local:audit-webhooks:install-captures-table` to create local capture storage.') }}
                </flux:callout.text>
            </flux:callout>
        @endif

        @if($driver === 'database' && $hasCaptureTable && $captures->isEmpty())
            <flux:callout color="slate" icon="circle-stack">
                <flux:callout.heading>{{ __('No local captures yet') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Send a test event from Organization Settings → Notifications → Audit Webhooks.') }}
                </flux:callout.text>
            </flux:callout>
        @endif

        @if($driver === 'database' && $hasCaptureTable && $captures->isNotEmpty())
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Received') }}</flux:table.column>
                        <flux:table.column>{{ __('Event') }}</flux:table.column>
                        <flux:table.column>{{ __('Mode') }}</flux:table.column>
                        <flux:table.column>{{ __('Response') }}</flux:table.column>
                        <flux:table.column>{{ __('Request') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($captures as $capture)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="text-xs text-slate-600">
                                        {{ $capture->received_at?->timezone(timezone())->diffForHumans() ?? 'n/a' }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $capture->received_at?->timezone(timezone())->toDateTimeString() ?? '' }}
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="font-medium text-slate-900">{{ $capture->event_type ?? 'unknown' }}</div>
                                    <div class="text-xs text-slate-500 break-all">{{ $capture->event_id ?? 'n/a' }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge
                                        color="{{ $capture->mode === 'fail' ? 'red' : ($capture->mode === 'slow' ? 'yellow' : 'green') }}"
                                        size="sm">
                                        {{ $capture->mode }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="text-xs text-slate-700">{{ $capture->response_status }}</div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="space-y-1 text-xs text-slate-600">
                                        <div>{{ strtoupper($capture->http_method) }}</div>
                                        <div class="break-all">{{ $capture->request_url }}</div>
                                        @if($capture->signature_header)
                                            <div class="break-all text-slate-500">{{ __('Signature:') }} {{ $capture->signature_header }}</div>
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>
</x-layouts.app>
