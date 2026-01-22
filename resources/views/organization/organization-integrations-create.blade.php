<section class="w-full">
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        <div class="space-y-6 max-w-2xl">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg" level="2">{{ __('Create Integration') }}</flux:heading>
                    <flux:subheading>{{ __('Generate OAuth credentials for your integration.') }}</flux:subheading>
                </div>
                <flux:button
                    variant="ghost"
                    href="{{ route('organization.settings.integrations') }}"
                    wire:navigate>
                    {{ __('Back') }}
                </flux:button>
            </div>

            @if($statusMessage)
                <flux:callout class="mb-4" color="{{ $statusLevel === 'success' ? 'green' : 'red' }}" icon="{{ $statusLevel === 'success' ? 'check-circle' : 'exclamation-triangle' }}">
                    <flux:callout.heading>{{ $statusMessage }}</flux:callout.heading>
                </flux:callout>
            @endif

            @if(! $this->canAccessIntegrations)
                <div class="py-10 text-center space-y-6">
                    <x-paid-plan-required title="{{ __('Integrations are locked') }}" />
                </div>
            @else
                <x-section>
                    <x-slot:title>{{ __('Integration details') }}</x-slot:title>
                    <x-slot:subheading>{{ __('Provide the information needed to create OAuth credentials.') }}</x-slot:subheading>
                    <x-slot:actions>
                        <div class="flex items-center justify-end gap-4">
                            <flux:button variant="primary" wire:click="createIntegrationClient">
                                {{ __('Create') }}
                            </flux:button>
                        </div>
                    </x-slot:actions>
                    <form class="w-full space-y-6">
                        <flux:input
                            wire:model="name"
                            label="Name"
                            description:trailing="Example: Acme Internal Access"
                            required/>
                        <flux:input
                            wire:model="key"
                            label="Key"
                            description:trailing="Example: acme-internal"
                            required/>
                        <flux:input
                            wire:model="landingPage"
                            label="Landing page"
                            description:trailing="Required for partner integrations."
                            :required="$this->organization->is_partner"/>
                        <flux:textarea
                            wire:model="description"
                            label="Description"
                            description:trailing="Share what the integration and partner provide."
                            required
                            rows="4"/>
                        <div class="space-y-2">
                            <flux:input
                                type="file"
                                wire:model="logo"
                                label="Logo"
                                description:trailing="Square image, at least 512x512."
                                required
                                accept="image/*"/>
                            @error('logo')
                                <flux:text class="text-xs text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <flux:textarea
                            wire:model="redirectUris"
                            label="Redirect URIs"
                            description:trailing="One per line or comma-separated."
                            required
                            rows="4"/>
                        <div class="space-y-2">
                            <flux:text class="text-sm font-medium text-slate-900">{{ __('Default scopes') }}</flux:text>
                            <flux:text class="text-xs text-slate-500">{{ __('Select one or more read-only scopes.') }}</flux:text>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($availableScopes as $scopeKey => $scopeLabel)
                                    <flux:checkbox
                                        wire:model="defaultScopes"
                                        value="{{ $scopeKey }}"
                                        label="{{ $scopeLabel }}"/>
                                @endforeach
                            </div>
                            @error('defaultScopes')
                                <flux:text class="text-xs text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </form>
                </x-section>

                @if($clientId && $clientSecret)
                    <flux:separator variant="subtle" />
                    <x-section>
                        <x-slot:title>{{ __('Credentials') }}</x-slot:title>
                        <x-slot:subheading>{{ __('Copy these values now. The client secret is only shown once.') }}</x-slot:subheading>
                        <div class="w-full space-y-4">
                            <flux:input label="Client ID" readonly copyable value="{{ $clientId }}"/>
                            <flux:input label="Client secret" readonly copyable value="{{ $clientSecret }}"/>
                        </div>
                    </x-section>
                @endif
            @endif
        </div>
    </x-layouts.organization-settings>
</section>
