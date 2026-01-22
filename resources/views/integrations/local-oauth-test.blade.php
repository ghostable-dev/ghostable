<x-layouts.app :title="__('Local OAuth Test')">
    <div class="mx-auto max-w-3xl space-y-8">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('Local OAuth Test Client') }}</flux:heading>
            <flux:subheading>{{ __('Use this page to simulate a partner OAuth flow in local development.') }}</flux:subheading>
        </div>

        @if($error)
            <flux:callout color="red" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('OAuth test failed') }}</flux:callout.heading>
                <flux:callout.text>{{ $error }}</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('local.oauth-test.start') }}" class="space-y-6">
            @csrf

            <flux:input name="client_id" label="Client ID" value="{{ $clientId }}" required/>
            <flux:input name="client_secret" label="Client secret" value="{{ $clientSecret }}" required/>
            <flux:input name="redirect_uri" label="Redirect URI" value="{{ $redirectUri }}" required/>

            <div class="space-y-2">
                <flux:text class="text-sm font-medium text-slate-900">{{ __('Scopes') }}</flux:text>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($availableScopes as $scopeKey => $scopeLabel)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="scopes[]"
                                value="{{ $scopeKey }}"
                                class="h-4 w-4 rounded border-slate-300 text-slate-900"
                                @checked(in_array($scopeKey, $selectedScopes, true))>
                            <span>{{ $scopeLabel }}</span>
                        </label>
                    @endforeach
                </div>
                @error('scopes')
                    <flux:text class="text-xs text-red-600">{{ $message }}</flux:text>
                @enderror
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit">{{ __('Start OAuth flow') }}</flux:button>
            </div>
        </form>

        @if($tokenResponse)
            <flux:separator variant="subtle" />
            <div class="space-y-3">
                <flux:heading size="md">{{ __('Token Response') }}</flux:heading>
                <pre class="rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ json_encode($tokenResponse, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if($organizationResponse)
            <flux:separator variant="subtle" />
            <div class="space-y-3">
                <flux:heading size="md">{{ __('Organization Response') }}</flux:heading>
                <pre class="rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ json_encode($organizationResponse, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    </div>
</x-layouts.app>
