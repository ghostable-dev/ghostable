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
        <flux:tab.group>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <flux:tabs wire:model="tab" variant="segmented">
                    <flux:tab name="connected">{{ __('Connected') }}</flux:tab>
                    <flux:tab name="available">{{ __('Available') }}</flux:tab>
                    <flux:tab name="yours">{{ __('Yours') }}</flux:tab>
                </flux:tabs>
                <flux:button
                    variant="primary"
                    href="{{ route('organization.settings.integrations.create') }}"
                    wire:navigate>
                    {{ __('Create integration') }}
                </flux:button>
            </div>

            <flux:tab.panel name="connected">
                <div class="relative space-y-3">
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
                                                        href="{{ route('integrations.oauth.connect', ['provider' => $integration->key]) }}">
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
            </flux:tab.panel>

            <flux:tab.panel name="available">
                <div class="relative space-y-8">
                    <div class="space-y-3">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">{{ __('Partner integrations') }}</p>
                            <p class="text-sm text-slate-500">{{ __('Connect published partner apps to your organization.') }}</p>
                        </div>
                        <flux:separator variant="subtle" />

                        @if($this->publishedIntegrationClients->isEmpty())
                            <flux:callout color="slate" icon="sparkles">
                                <flux:callout.heading>{{ __('No partner integrations available') }}</flux:callout.heading>
                                <flux:callout.text>
                                    {{ __('Published partner apps will appear here once available.') }}
                                </flux:callout.text>
                            </flux:callout>
                        @else
                            <div class="grid gap-4 sm:grid-cols-2 mt-3">
                                @foreach($this->publishedIntegrationClients as $client)
                                    @php
                                        $redirect = $client->redirect_uris[0] ?? null;
                                        $scope = implode(' ', $client->default_scopes ?? []);
                                        $connectUrl = $redirect
                                            ? route('integrations.oauth.authorize', [
                                                'client_id' => $client->client_id,
                                                'redirect_uri' => $redirect,
                                                'response_type' => 'code',
                                                'scope' => $scope,
                                            ])
                                            : null;
                                    @endphp
                                    <x-integration.card
                                        :name="$client->name"
                                        :logo="$client->logoUrl()"
                                        :description="$client->description"
                                        :subtitle="__('Built by Partner')">
                                        <div class="flex items-center justify-between pt-2">
                                            <flux:button
                                                variant="ghost"
                                                wire:click="$dispatch('integration-details:open', { source: 'partner', id: '{{ $client->id }}' })">
                                                {{ __('Details') }}
                                            </flux:button>
                                            @if($connectUrl)
                                                <flux:button
                                                    variant="primary"
                                                    href="{{ $connectUrl }}"
                                                    wire:navigate>
                                                    {{ __('Connect') }}
                                                </flux:button>
                                            @else
                                                <flux:button variant="ghost" color="slate" icon="lock-closed" disabled>
                                                    {{ __('Connect') }}
                                                </flux:button>
                                            @endif
                                        </div>
                                    </x-integration.card>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">{{ __('Ghostable integrations') }}</p>
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
                                    <x-integration.card
                                        :name="$meta['name']"
                                        :logo="$meta['logo'] ?? null"
                                        :description="$meta['description'] ?? null"
                                        :subtitle="__('Built by Ghostable')">
                                        <div class="flex items-center justify-between pt-2">
                                            <flux:button
                                                variant="ghost"
                                                wire:click="$dispatch('integration-details:open', { source: 'ghostable', id: '{{ $key }}' })">
                                                {{ __('Details') }}
                                            </flux:button>
                                            @if(($meta['oauth'] ?? false) === true)
                                                @if($canAccessIntegrations)
                                                    <flux:button
                                                        variant="primary"
                                                        href="{{ route('integrations.oauth.connect', ['provider' => $key]) }}">
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
                                    </x-integration.card>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </flux:tab.panel>

            <flux:tab.panel name="yours">
                <div class="relative space-y-3">
                    <div>
                        <p class="text-lg font-semibold text-slate-900">{{ __('Your integrations') }}</p>
                        <p class="text-sm text-slate-500">{{ __('Manage your organization-owned integrations.') }}</p>
                    </div>
                    <flux:separator variant="subtle" />

                    @if($this->integrationClients->isEmpty())
                        <flux:callout color="slate" icon="bolt">
                            <flux:callout.heading>{{ __('No integrations created yet') }}</flux:callout.heading>
                            <flux:callout.text>
                                {{ __('Create your first integration to generate OAuth credentials.') }}
                            </flux:callout.text>
                        </flux:callout>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 mt-3">
                            @foreach($this->integrationClients as $client)
                                <x-integration.card
                                    :name="$client->name"
                                    :logo="$client->logoUrl()"
                                    :description="$client->description"
                                    :subtitle="__('Built by you')">
                                    <x-slot:badge>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            {{ ucfirst($client->publish_status ?? 'draft') }}
                                        </span>
                                    </x-slot:badge>
                                    <p class="text-sm leading-6 text-slate-700">
                                        {{ __('Default scopes:') }}
                                        {{ $client->default_scopes ? implode(', ', $client->default_scopes) : __('None') }}
                                    </p>
                                    <div class="flex justify-end pt-2">
                                        <flux:button
                                            variant="ghost"
                                            href="{{ route('organization.settings.integrations.edit', ['client' => $client->id]) }}"
                                            wire:navigate>
                                            {{ __('Edit') }}
                                        </flux:button>
                                    </div>
                                </x-integration.card>
                            @endforeach
                        </div>
                    @endif
                </div>
            </flux:tab.panel>
        </flux:tab.group>

        <livewire:integration.livewire.integration-settings-flyout/>
        <livewire:integration.livewire.integration-details-flyout/>
        @endif
    </x-layouts.organization-settings>
</section>
