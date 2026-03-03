<div class="space-y-6 max-w-4xl">
    @php
        $metricsPayload = $this->auditWebhookMetricsPayload;
        $metricsSummary = $metricsPayload['summary'] ?? [];
        $metricsByWebhook = collect($metricsPayload['webhooks'] ?? [])->keyBy('id');
    @endphp

    <x-section>
        <x-slot:title>{{ __('Audit Webhooks') }}</x-slot:title>
        <x-slot:subheading>
            {{ __('Stream organization audit events to your SIEM/SOC endpoints with signed deliveries.') }}
        </x-slot:subheading>

        @if($statusMessage)
            <flux:callout
                class="mb-4"
                color="{{ $statusLevel === 'success' ? 'green' : ($statusLevel === 'error' ? 'red' : 'slate') }}"
                icon="{{ $statusLevel === 'success' ? 'check-circle' : ($statusLevel === 'error' ? 'exclamation-triangle' : 'information-circle') }}">
                <flux:callout.heading>{{ $statusMessage }}</flux:callout.heading>
            </flux:callout>
        @endif

        @if(! $this->canManageAuditWebhooks)
            <flux:callout color="slate" icon="lock-closed">
                <flux:callout.heading>{{ __('Admin access required') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Only organization admins can create or manage audit webhook endpoints.') }}
                </flux:callout.text>
            </flux:callout>
        @else
            <div class="space-y-4">
                <div class="flex items-center justify-end">
                    <flux:modal.trigger name="create-audit-webhook">
                        <flux:button variant="primary">
                            {{ __('Add webhook') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>

            @if($this->localAuditReceiverEnabled)
                <flux:callout class="mt-2 mb-4" color="slate" icon="beaker">
                    <flux:callout.heading>{{ __('Local receiver presets') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Auto-fill endpoint URLs for local audit webhook testing.') }}
                    </flux:callout.text>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <flux:button variant="ghost" wire:click="useLocalReceiver('ok')">
                            {{ __('Use local receiver') }}
                        </flux:button>
                        <flux:button variant="ghost" wire:click="useLocalReceiver('fail')">
                            {{ __('Use failure receiver') }}
                        </flux:button>
                        <flux:button variant="ghost" wire:click="useLocalReceiver('slow')">
                            {{ __('Use slow receiver') }}
                        </flux:button>
                        @if($this->localAuditReceiverInboxUrl)
                            <flux:button variant="subtle" href="{{ $this->localAuditReceiverInboxUrl }}">
                                {{ __('Open local inbox') }}
                            </flux:button>
                        @endif
                    </div>
                </flux:callout>
            @endif

            @if($lastSigningSecret)
                <flux:callout color="yellow" icon="information-circle">
                    <flux:callout.heading>{{ __('Save this signing secret now') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('This secret is shown once for') }} <strong>{{ $lastSigningSecretName }}</strong>.
                        {{ __('Store it securely to verify webhook signatures.') }}
                    </flux:callout.text>
                </flux:callout>
                <div class="space-y-2">
                    <flux:input
                        label="Signing secret"
                        readonly
                        copyable
                        value="{{ $lastSigningSecret }}" />
                    <div class="flex items-center justify-end">
                        <flux:button variant="ghost" wire:click="clearLastSigningSecret">
                            {{ __('Dismiss') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        @endif

        <div class="space-y-4">
            <flux:callout color="slate" icon="chart-bar-square">
                <flux:callout.heading>
                    {{ __('Attempts:') }} {{ (int) ($metricsSummary['attempted'] ?? 0) }}
                    · {{ __('Success rate:') }}
                    @if(array_key_exists('success_rate', $metricsSummary) && $metricsSummary['success_rate'] !== null)
                        {{ number_format((float) $metricsSummary['success_rate'], 2) }}%
                    @else
                        n/a
                    @endif
                    · {{ __('Dead-letter webhooks:') }} {{ (int) ($metricsSummary['dead_lettered_webhooks'] ?? 0) }}
                </flux:callout.heading>
                <flux:callout.text>
                    {{ __('Latency p50/p95/p99 (ms):') }}
                    {{ $metricsSummary['latency_p50'] ?? 'n/a' }} /
                    {{ $metricsSummary['latency_p95'] ?? 'n/a' }} /
                    {{ $metricsSummary['latency_p99'] ?? 'n/a' }}
                </flux:callout.text>
            </flux:callout>
        </div>

        @if($this->auditWebhooks->isEmpty())
            <flux:callout color="slate" icon="circle-stack">
                <flux:callout.heading>{{ __('No audit webhooks configured') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Create a destination to start streaming organization audit events.') }}
                </flux:callout.text>
            </flux:callout>
        @else
            <div class="overflow-x-auto">
            <flux:table class="w-full table-fixed" style="table-layout: fixed;">
                    <flux:table.columns>
                    <flux:table.column class="w-[12rem] min-w-0">{{ __('Webhook') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Updated') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->auditWebhooks as $webhook)
                        <flux:table.row wire:key="audit-webhook-{{ $webhook->id }}">
                            <flux:table.cell>
                                <div class="space-y-1">
                                    <span class="font-medium text-slate-900 truncate" style="display: block; width: 100%; max-width: 28rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $webhook->name }}
                                    </span>
                                    <p class="text-xs text-slate-500 truncate" style="display: block; width: 100%; max-width: 28rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $webhook->endpoint_url }}">
                                        {{ $webhook->endpoint_url }}
                                    </p>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $statusColor = match ($webhook->status?->value ?? $webhook->status) {
                                        'active' => 'green',
                                        'disabled' => 'yellow',
                                        'dead_letter' => 'red',
                                        default => 'slate',
                                    };
                                @endphp
                                <flux:badge color="{{ $statusColor }}" size="sm">
                                    {{ str_replace('_', ' ', $webhook->status?->value ?? (string) $webhook->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="space-y-1 text-xs text-slate-600">
                                    <div>
                                        {{ __('Last delivered:') }}
                                        {{ $webhook->last_delivered_at?->timezone(timezone())->diffForHumans() ?? 'never' }}
                                    </div>
                                    <div>
                                        {{ __('Updated:') }}
                                        {{ $webhook->updated_at?->timezone(timezone())->diffForHumans() ?? 'n/a' }}
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="right" class="w-56 min-w-0" style="width: 14rem;">
                                @if($this->canManageAuditWebhooks)
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:dropdown position="left">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="ellipsis-horizontal">
                                            </flux:button>
                                            <flux:menu>
                                                <flux:menu.item wire:click="openWebhookMetrics('{{ $webhook->id }}')">
                                                    {{ __('View metrics') }}
                                                </flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item wire:click="testWebhook('{{ $webhook->id }}')">
                                                    {{ __('Send test event') }}
                                                </flux:menu.item>
                                                <flux:menu.item wire:click="rotateWebhookSecret('{{ $webhook->id }}')">
                                                    {{ __('Rotate signing secret') }}
                                                </flux:menu.item>
                                                @if(($webhook->status?->value ?? $webhook->status) === 'active')
                                                    <flux:menu.item
                                                        wire:click="disableWebhook('{{ $webhook->id }}')"
                                                        variant="danger">
                                                        {{ __('Disable webhook') }}
                                                    </flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            </div>

            <flux:modal name="audit-webhook-metrics" class="md:w-[38rem]">
                <div class="space-y-4">
                    <flux:heading size="lg">{{ __('Webhook delivery metrics') }}</flux:heading>

                    @if($selectedWebhookId)
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-semibold">{{ __('Name:') }}</span>
                                {{ $selectedWebhookName }}
                            </div>
                            <div>
                                <span class="font-semibold">{{ __('Status:') }}</span>
                                {{ $selectedWebhookStatus }}
                            </div>
                            <div class="break-all">
                                <span class="font-semibold">{{ __('Endpoint:') }}</span>
                                {{ $selectedWebhookEndpoint }}
                            </div>
                            <div>
                                <flux:select label="Metrics window" wire:model.live="metricsWindow">
                                    <flux:select.option value="24h">{{ __('Last 24 hours') }}</flux:select.option>
                                    <flux:select.option value="7d">{{ __('Last 7 days') }}</flux:select.option>
                                    <flux:select.option value="30d">{{ __('Last 30 days') }}</flux:select.option>
                                </flux:select>
                            </div>
                            <div>
                                <span class="font-semibold">{{ __('Window:') }}</span>
                                {{ strtoupper($metricsWindow) }}
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-3 text-sm">
                            <div>{{ __('Attempted:') }} {{ (int) ($selectedWebhookMetrics['attempted'] ?? 0) }}</div>
                            <div>{{ __('Succeeded:') }} {{ (int) ($selectedWebhookMetrics['succeeded'] ?? 0) }}</div>
                            <div>{{ __('Failed:') }} {{ (int) ($selectedWebhookMetrics['failed'] ?? 0) }}</div>
                            <div>
                                {{ __('Success rate:') }}
                                @if(array_key_exists('success_rate', $selectedWebhookMetrics) && $selectedWebhookMetrics['success_rate'] !== null)
                                    {{ number_format((float) $selectedWebhookMetrics['success_rate'], 2) }}%
                                @else
                                    n/a
                                @endif
                            </div>
                            <div>
                                {{ __('Latency p50/p95/p99 (ms):') }}
                                {{ $selectedWebhookMetrics['latency_p50'] ?? 'n/a' }} /
                                {{ $selectedWebhookMetrics['latency_p95'] ?? 'n/a' }} /
                                {{ $selectedWebhookMetrics['latency_p99'] ?? 'n/a' }}
                            </div>
                            <div>
                                {{ __('Last attempted:') }} {{ $selectedWebhookMetrics['last_attempted_at'] ?? 'n/a' }}
                            </div>
                            <div>
                                {{ __('Last succeeded:') }} {{ $selectedWebhookMetrics['last_succeeded_at'] ?? 'n/a' }}
                            </div>
                            <div>
                                {{ __('Last failed:') }} {{ $selectedWebhookMetrics['last_failed_at'] ?? 'n/a' }}
                            </div>
                            <div class="text-slate-500">
                                {{ __('Consecutive failures:') }} {{ (int) ($selectedWebhookMetrics['consecutive_failures'] ?? 0) }}
                            </div>
                        </div>
                    @else
                        <flux:callout color="slate">
                            <flux:callout.text>{{ __('Select a webhook row to view details.') }}</flux:callout.text>
                        </flux:callout>
                    @endif

                    <div class="flex items-center justify-end">
                        <flux:modal.close>
                            <flux:button variant="primary">
                                {{ __('Done') }}
                            </flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        @endif

        @if($this->canManageAuditWebhooks)
            <flux:modal name="create-audit-webhook" class="md:w-[36rem]">
                <form wire:submit="createWebhook" class="space-y-5">
                    <flux:heading size="lg">{{ __('Create audit webhook') }}</flux:heading>
                    <flux:input
                        wire:model="name"
                        label="Webhook name"
                        description:trailing="Example: Security SIEM"
                        required />

                    <flux:input
                        wire:model="endpointUrl"
                        label="Endpoint URL"
                        type="url"
                        description:trailing="Must be https:// or http://"
                        required />

                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">
                                {{ __('Cancel') }}
                            </flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">
                            {{ __('Save webhook') }}
                        </flux:button>
                    </div>
                </form>
            </flux:modal>
        @endif
    </x-section>
</div>
