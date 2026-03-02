<div class="space-y-6 max-w-4xl">
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
            <form wire:submit="createWebhook" class="space-y-4">
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

                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit">
                        {{ __('Add webhook') }}
                    </flux:button>
                </div>
            </form>

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

        @if($this->auditWebhooks->isEmpty())
            <flux:callout color="slate" icon="circle-stack">
                <flux:callout.heading>{{ __('No audit webhooks configured') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Create a destination to start streaming organization audit events.') }}
                </flux:callout.text>
            </flux:callout>
        @else
            <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Webhook') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Delivery') }}</flux:table.column>
                    <flux:table.column>{{ __('Updated') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->auditWebhooks as $webhook)
                        <flux:table.row wire:key="audit-webhook-{{ $webhook->id }}">
                            <flux:table.cell>
                                <div class="space-y-1">
                                    <div class="font-medium text-slate-900">{{ $webhook->name }}</div>
                                    <div class="text-xs text-slate-500 break-all">{{ $webhook->endpoint_url }}</div>
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
                                        {{ __('Failures:') }} {{ (int) $webhook->consecutive_failures }}
                                    </div>
                                    @if($webhook->last_error)
                                        <div class="text-red-600">{{ $webhook->last_error }}</div>
                                    @endif
                                </div>
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
                            <flux:table.cell align="right">
                                @if($this->canManageAuditWebhooks)
                                    <flux:dropdown position="left">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="ellipsis-horizontal">
                                        </flux:button>
                                        <flux:menu>
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
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            </div>
        @endif
    </x-section>
</div>
