<section class="w-full">
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        @if($statusMessage)
            <flux:callout class="mb-4" color="{{ $statusLevel === 'success' ? 'green' : 'red' }}" icon="{{ $statusLevel === 'success' ? 'check-circle' : 'exclamation-triangle' }}">
                <flux:callout.heading>{{ $statusMessage }}</flux:callout.heading>
            </flux:callout>
        @endif

        @if(! $canAccessIntegrations)
            <div class="py-10 text-center space-y-6">
                <x-paid-plan-required title="{{ __('Integrations are locked') }}" />
            </div>
        @else
        <div class="relative space-y-8">

            <div class="space-y-3">
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Connected Integrations') }}</p>
                    <p class="text-sm text-slate-500">{{ __('Enable or disable integrations for this organization.') }}</p>
                </div>
                <flux:separator variant="subtle" />

                @if($this->integrations->isEmpty())
                    <flux:callout color="slate" icon="squares-plus">
                        <flux:callout.heading>{{ __('No integrations connected yet') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __('Choose an integration below to get started.') }}
                        </flux:callout.text>
                    </flux:callout>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 mt-3">
                        @foreach($this->integrations as $integration)
                        @php
                            $meta = $available[$integration->key] ?? ['name' => ucfirst($integration->key), 'description' => ''];
                            $headerTextClass = 'text-slate-900';
                            $subheaderTextClass = 'text-slate-500';
                        @endphp
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-4">
                                @if(! empty($meta['logo']))
                                    <img src="{{ $meta['logo'] }}" alt="{{ $meta['name'] }}" class="h-12 w-12 rounded-lg bg-white object-cover ring-1 ring-slate-200">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                                        {{ strtoupper(substr($meta['name'], 0, 1)) }}
                                    </div>
                                @endif
                                <div class="space-y-1 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold {{ $headerTextClass }}">{{ $meta['name'] }}</p>
                                    </div>
                                    <p class="text-xs {{ $subheaderTextClass }}">{{ __('Connected') }}</p>
                                </div>
                                <flux:icon name="check-circle" class="h-5 w-5 text-emerald-500" />
                            </div>
                            <div class="space-y-3 px-4 py-4">
                                <p class="text-sm leading-6 text-slate-700">{{ $meta['description'] }}</p>
                                <div class="flex items-center justify-between pt-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($integration->key === 'vanta')
                                            @if($canAccessIntegrations)
                                                <flux:button
                                                    variant="ghost"
                                                    color="slate"
                                                    wire:click="$dispatch('integration-settings:open', { integrationId: '{{ $integration->id }}' })">
                                                    {{ __('Details') }}
                                                </flux:button>
                                            @else
                                                <flux:button variant="ghost" color="slate" icon="lock-closed" disabled>
                                                    {{ __('Details') }}
                                                </flux:button>
                                            @endif
                                        @elseif(($meta['oauth'] ?? false) === true)
                                            @if($canAccessIntegrations)
                                                <flux:button
                                                    variant="ghost"
                                                    color="slate"
                                                    href="{{ route('integrations.oauth.connect', ['provider' => $integration->key]) }}"
                                                    wire:navigate>
                                                    {{ __('Reconnect') }}
                                                </flux:button>
                                            @else
                                                <flux:button variant="ghost" color="slate" icon="lock-closed" disabled>
                                                    {{ __('Reconnect') }}
                                                </flux:button>
                                            @endif
                                        @endif
                                    </div>
                                    @if($canAccessIntegrations)
                                        <flux:button
                                            variant="ghost"
                                            color="red"
                                            wire:click="disconnectIntegration('{{ $integration->id }}')">
                                            {{ __('Disable') }}
                                        </flux:button>
                                    @else
                                        <flux:button variant="ghost" color="slate" icon="lock-closed" disabled>
                                            {{ __('Locked') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ __('Available Integrations') }}</p>
                    <p class="text-sm text-slate-500">{{ __('Connect new providers to your organization.') }}</p>
                </div>
                <flux:separator variant="subtle" />

                @php
                    $connectedKeys = $this->integrations->pluck('key')->all();
                    $availableToShow = array_filter($available, fn ($meta, $key) => ! in_array($key, $connectedKeys), ARRAY_FILTER_USE_BOTH);
                @endphp

                @if(empty($availableToShow))
                    <flux:callout color="slate" icon="check-circle">
                        <flux:callout.heading>{{ __('All integrations are connected') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __('You have connected every available provider.') }}
                        </flux:callout.text>
                    </flux:callout>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 mt-3">
                        @foreach($availableToShow as $key => $meta)
                            @php
                                $headerTextClass = 'text-slate-900';
                                $subheaderTextClass = 'text-slate-500';
                            @endphp
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-50 px-4 py-4">
                                    @if(! empty($meta['logo']))
                                        <img src="{{ $meta['logo'] }}" alt="{{ $meta['name'] }}" class="h-12 w-12 rounded-lg bg-white object-cover ring-1 ring-slate-200">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                                            {{ strtoupper(substr($meta['name'], 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold {{ $headerTextClass }}">{{ $meta['name'] }}</p>
                                        <p class="text-xs {{ $subheaderTextClass }}">{{ __('Not connected') }}</p>
                                    </div>
                                </div>
                                <div class="space-y-3 px-4 py-4">
                                    <p class="text-sm leading-6 text-slate-700">{{ $meta['description'] }}</p>
                                    <div class="flex justify-end pt-2">
                                        @if(($meta['oauth'] ?? false) === true)
                                            @if($canAccessIntegrations)
                                                <flux:button
                                                    variant="primary"
                                                    href="{{ route('integrations.oauth.connect', ['provider' => $key]) }}"
                                                    wire:navigate>
                                                    {{ __('Connect') }}
                                                </flux:button>
                                            @else
                                                <flux:button variant="ghost" color="slate" icon="lock-closed" disabled>
                                                    {{ __('Connect') }}
                                                </flux:button>
                                            @endif
                                        @else
                                            @if($canAccessIntegrations)
                                                <flux:button
                                                    variant="primary"
                                                    wire:click="connectIntegration('{{ $key }}')">
                                                    {{ __('Enable') }}
                                                </flux:button>
                                            @else
                                                <flux:button variant="ghost" color="slate" icon="lock-closed" disabled>
                                                    {{ __('Enable') }}
                                                </flux:button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <livewire:integration.livewire.integration-settings-flyout/>
        @endif
    </x-layouts.organization-settings>
</section>
