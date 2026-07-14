@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

<x-layouts.guest title="Manage your Ghostable licenses">
    <section class="bg-white px-6 py-16 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-3xl">
            <div class="flex size-12 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                <flux:icon.key class="size-6" />
            </div>

            <flux:heading size="xl" class="mt-6">Manage your licenses</flux:heading>
            <flux:text class="mt-3 max-w-2xl">
                These are the active licenses purchased with your verified email address. This browser has temporary, read-only access, so you can return here without requesting another email.
            </flux:text>

            <div class="mt-8 grid gap-4">
                @foreach($managedLicenses as $managedLicense)
                    <flux:card class="p-6 md:p-8">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <flux:heading size="lg">{{ $managedLicense['license']->plan->label() }}</flux:heading>
                                <flux:text class="mt-1">{{ $managedLicense['license']->organization->name }}</flux:text>
                            </div>
                            <flux:badge color="green">{{ $managedLicense['license']->status->label() }}</flux:badge>
                        </div>

                        <dl class="mt-6 grid grid-cols-2 gap-4 rounded-lg bg-zinc-50 p-4 text-sm sm:grid-cols-3">
                            <div>
                                <dt class="text-zinc-500">Seats</dt>
                                <dd class="mt-1 font-medium text-zinc-950">{{ $managedLicense['license']->seat_count }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500">Devices</dt>
                                <dd class="mt-1 font-medium text-zinc-950">
                                    {{ $managedLicense['license']->active_activations_count ?? 0 }} / {{ $managedLicense['license']->activation_limit }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500">Updates through</dt>
                                <dd class="mt-1 font-medium text-zinc-950">
                                    {{ $managedLicense['license']->updates_until?->format('M j, Y') ?? 'N/A' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-6">
                            <flux:input
                                label="License key"
                                value="{{ $managedLicense['licenseKey'] }}"
                                readonly
                                copyable
                            />
                        </div>
                    </flux:card>
                @endforeach
            </div>

            <flux:callout icon="shield-check" color="blue" class="mt-8">
                <flux:callout.heading>Temporary browser access</flux:callout.heading>
                <flux:callout.text>
                    This does not sign you into Ghostable or grant access to an organization. Sign in for full account and team management.
                </flux:callout.text>
            </flux:callout>

            <div class="mt-8 flex flex-wrap gap-3">
                <flux:button href="{{ route('login') }}" variant="primary">Sign in</flux:button>
                <flux:button href="{{ route('licenses') }}" variant="ghost">Purchase another license</flux:button>
            </div>
        </div>
    </section>
</x-layouts.guest>
