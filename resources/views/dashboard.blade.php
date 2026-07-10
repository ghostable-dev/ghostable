<x-layouts.app :title="__('Dashboard')">
    @php
        $googleTagId = config('services.google_tag.id');
        $accountCreatedLabel = config('services.google_tag.account_created_label');
        $xTagId = config('services.x_tag.id');
        $xAccountCreatedEventId = config('services.x_tag.account_created_event_id');
        $shouldTrackAccountCreated = request()->boolean('account_created')
            && filled($googleTagId)
            && filled($accountCreatedLabel)
            && auth()->check()
            && auth()->user()->hasVerifiedEmail();
        $shouldTrackXAccountCreated = request()->boolean('account_created')
            && filled($xTagId)
            && filled($xAccountCreatedEventId)
            && auth()->check()
            && auth()->user()->hasVerifiedEmail();
        $currentOrganization = auth()->user()?->currentOrganization();
        $usesLegacyProjectExperience = $currentOrganization?->usesLegacyProjectExperience() ?? false;
    @endphp

    <x-slot name="subheader">
        @if(auth()->check() && auth()->user()->organizations->count())
            <div class="w-full bg-white pt-2">
                <div class="w-full px-6 lg:px-8">
                    <flux:navbar>
                        <flux:navbar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            Overview
                        </flux:navbar.item>
                        @if($usesLegacyProjectExperience)
                            <flux:navbar.item :href="route('projects')" :current="request()->routeIs('projects')" wire:navigate>
                                Projects
                            </flux:navbar.item>
                        @endif
                        <flux:navbar.item
                            :href="route('organization.settings.general')"
                            :current="request()->routeIs('organization.settings.*')"
                            wire:navigate>
                            Settings
                        </flux:navbar.item>
                    </flux:navbar>
                </div>
            </div>
        @endif
    </x-slot>

    @if($shouldTrackAccountCreated)
        @include('components.google-tag.script', [
            'id' => $googleTagId,
            'event' => 'conversion',
            'payload' => [
                'send_to' => "{$googleTagId}/{$accountCreatedLabel}",
                'transaction_id' => 'account-created-'.auth()->id(),
            ],
        ])
    @endif

    @if($shouldTrackXAccountCreated)
        @include('components.x-tag.script', [
            'id' => $xTagId,
            'event' => $xAccountCreatedEventId,
        ])
    @endif

    @php
        $recentProjects = $usesLegacyProjectExperience
            ? $currentOrganization
                ?->projects()
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get() ?? collect()
            : collect();
    @endphp

    <div>
        {{-- Pending Invites --}}
        <livewire:account.livewire.pending-invites/>

        @if(auth()->user()->organizations->count())
            <section class="w-full space-y-4">
                <div class="relative w-full -mt-2">
                    <flux:heading size="xl" level="1">
                        {{ $currentOrganization?->name ?? __('Overview') }}
                    </flux:heading>
                    <flux:separator variant="subtle" class="mt-4" />
                </div>

                @if(! $usesLegacyProjectExperience)
                    <flux:callout icon="key" color="blue">
                        <flux:callout.heading>{{ __('Desktop licensing is enabled') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __('This organization uses license-based desktop access. Projects, integrations, and the legacy V2 workflow are hidden for this organization.') }}
                        </flux:callout.text>
                        <x-slot name="actions">
                            <flux:button href="{{ route('organization.settings.billing') }}" variant="primary" wire:navigate>
                                {{ __('View licensing') }}
                            </flux:button>
                        </x-slot>
                    </flux:callout>
                @else
                    <div>
                        <flux:heading size="lg" level="2">{{ __('Recent Projects') }}</flux:heading>
                    </div>

                    @if($recentProjects->isEmpty())
                        <div class="space-y-2">
                            <flux:heading size="sm">{{ __('No projects yet') }}</flux:heading>
                            <flux:subheading>{{ __('Create a project to get started.') }}</flux:subheading>
                        </div>
                    @else
                        <ul role="list" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3 auto-rows-fr">
                            @foreach($recentProjects as $project)
                                <li class="col-span-1" wire:key="recent-project-{{ $project->id }}">
                                    <flux:callout icon="circle-stack" class="h-full min-h-[180px] flex flex-col bg-white">
                                        <flux:callout.heading>
                                            <flux:link href="{{ route('project.environments', $project) }}" wire:navigate>
                                                {{ $project->name }}
                                            </flux:link>
                                        </flux:callout.heading>
                                        <flux:callout.text>
                                            Select an environment from below.
                                        </flux:callout.text>
                                        <x-slot name="actions">
                                            @php
                                                $environments = $project->environments;
                                                $total = $environments->count();
                                            @endphp

                                            <div class="flex flex-wrap gap-2">
                                                @foreach($environments->take(4) as $env)
                                                    <flux:link href="{{ route('environment.variables', $env) }}" wire:navigate>
                                                        {{ str()->limit($env->name, 15) }}
                                                    </flux:link>
                                                @endforeach

                                                @if($total > 4)
                                                    <flux:link>
                                                        @php $remaining = $total - 4; @endphp
                                                        and {{ $remaining }} {{ str()->plural('other', $remaining) }}
                                                    </flux:link>
                                                @endif
                                            </div>
                                        </x-slot>
                                    </flux:callout>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-8">
                        <flux:heading size="lg" level="2">{{ __('Variable Promotion Requests') }}</flux:heading>
                        <flux:subheading>{{ __('Pending cross-environment variable promotions that require review.') }}</flux:subheading>
                    </div>

                    <flux:card class="border-zinc-200/80 bg-white shadow-none">
                        <livewire:organization.livewire.organization-variable-promotion-requests-manager :compact="true" />
                    </flux:card>

                    <div class="mt-8">
                        <flux:heading size="lg" level="2">{{ __('Environment Key Re-share Queue') }}</flux:heading>
                        <flux:subheading>{{ __('Pending requests for newly linked devices or newly authorized members.') }}</flux:subheading>
                    </div>

                    <flux:card class="border-zinc-200/80 bg-white shadow-none">
                        <livewire:organization.livewire.organization-key-reshare-requests-manager />
                    </flux:card>
                @endif
            </section>

            <livewire:organization.livewire.organization-switcher-modal/>
        @else
            <div class="space-y-6 text-center">
                <flux:heading size="md">{{ __('No organizations yet') }}</flux:heading>
                <flux:subheading>{{ __('Create an organization to get started.') }}</flux:subheading>
                <flux:modal.trigger name="create-organization">
                    <flux:button variant="primary">{{ __('Create Organization') }}</flux:button>
                </flux:modal.trigger>
            </div>

            <livewire:organization.livewire.organization-create-modal/>
        @endif
    </div>

</x-layouts.app>
