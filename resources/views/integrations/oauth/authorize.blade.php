<x-layouts.auth :title="__('Authorize Integration')">
    <div class="mx-auto w-full max-w-xl space-y-8 text-white">
        <div class="space-y-4">
            <x-auth-header
                :title="__('Authorize') . ' ' . $client->name"
                :description="__('This integration will be able to access Ghostable data for the organization you choose.')"
            />
            <div class="flex items-center justify-center">
                <div class="flex items-center gap-4 rounded-2xl border border-zinc-800 bg-zinc-900 px-5 py-4 shadow-sm">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-black ring-1 ring-zinc-800">
                        <img
                            src="{{ asset('favicon.svg') }}"
                            alt="{{ __('Ghostable') }}"
                            class="h-7 w-auto">
                    </div>
                    <div class="flex items-center gap-2 text-zinc-400">
                        <span class="h-px w-10 bg-zinc-700"></span>
                        <span class="text-xs font-semibold uppercase tracking-widest">{{ __('Connect') }}</span>
                        <span class="h-px w-10 bg-zinc-700"></span>
                    </div>
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-zinc-800 text-sm font-semibold text-white ring-1 ring-zinc-700">
                        {{ strtoupper(substr($client->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        </div>

        @if($organizations->isEmpty())
            <flux:callout variant="warning">
                <flux:callout.heading>{{ __('No organizations found') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('You must belong to an organization before connecting an integration.') }}
                </flux:callout.text>
            </flux:callout>
        @else
            <form method="POST" action="{{ route('integrations.oauth.approve') }}" class="space-y-6">
                @csrf

                <input type="hidden" name="client_id" value="{{ $client->client_id }}">
                <input type="hidden" name="redirect_uri" value="{{ $redirectUri }}">
                <input type="hidden" name="response_type" value="{{ $responseType }}">
                <input type="hidden" name="scope" value="{{ $scopeString }}">
                <input type="hidden" name="state" value="{{ $state }}">
                <input type="hidden" name="code_challenge" value="{{ $codeChallenge }}">
                <input type="hidden" name="code_challenge_method" value="{{ $codeChallengeMethod }}">

                <div class="space-y-2" x-data="{ selected: '{{ $selectedOrganizationId ?? ($organizations->first()?->id ?? '') }}' }">
                    <flux:select
                        :label="__('Organization')"
                        variant="listbox"
                        :placeholder="__('Select organization...')"
                        x-model="selected"
                        required>
                        @foreach($organizations as $organization)
                            <flux:select.option
                                value="{{ $organization->id }}">
                                {{ $organization->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <input type="hidden" name="organization_id" x-bind:value="selected">
                </div>

                <div class="space-y-2">
                    <flux:label>{{ __('Requested access') }}</flux:label>
                    @if(empty($scopes))
                        <p class="text-sm text-zinc-400">{{ __('No specific scopes requested.') }}</p>
                    @else
                        <div class="space-y-2">
                            @foreach($scopes as $scope)
                                <div class="flex items-center gap-2 text-sm text-zinc-200">
                                    <flux:icon name="check-circle" class="h-4 w-4 text-emerald-400" />
                                    <span>{{ $scope }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4 text-xs text-zinc-400">
                    <p>
                        {{ __('Make sure that you trust') }}
                        <span class="font-semibold text-zinc-200">{{ $client->name }}</span>.
                        {{ __('You may be sharing sensitive info with this site or app. You can always see or remove access in your organization settings.') }}
                    </p>
                    {{-- <p class="mt-3">
                        <flux:link href="{{ route('trust') }}" variant="subtle">
                            {{ __('Learn how Ghostable helps you share data safely.') }}
                        </flux:link>
                    </p> --}}
                </div>

                <div class="flex items-center justify-between gap-3">
                    <flux:button variant="ghost" type="submit" name="action" value="deny">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit" name="action" value="approve">
                        {{ __('Authorize') }}
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.auth>
