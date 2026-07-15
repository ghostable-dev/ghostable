@php
    $offers = [
        [
            'name' => 'Personal',
            'plan' => 'personal',
            'price' => '$49',
            'description' => 'For one developer managing environments across their everyday devices.',
            'seats' => '1 seat',
            'devices' => '2 device activations',
            'features' => ['Ghostable Desktop', 'Git-controlled environments', 'Background sync', 'One-click encrypt and decrypt', 'Multi-project management'],
        ],
        [
            'name' => 'Team 5',
            'plan' => 'team_5',
            'price' => '$249',
            'description' => 'For small teams standardizing secure environment workflows.',
            'seats' => '5 seats',
            'devices' => '5 device activations',
            'featured' => true,
            'features' => ['Everything in Personal', 'Shared license management', 'Reassignable seats', 'Team environment workflows', 'One year of updates'],
        ],
        [
            'name' => 'Team 10',
            'plan' => 'team_10',
            'price' => '$499',
            'description' => 'For growing teams bringing more projects and developers under control.',
            'seats' => '10 seats',
            'devices' => '10 device activations',
            'features' => ['Everything in Team 5', '10 managed seats', 'Reassignable activations', 'Multi-project management', 'One year of updates'],
        ],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="Ghostable Desktop pricing"
        description="One-time Ghostable Desktop licenses for individuals and teams. Start at $49 with one year of updates and no account required."
        :keywords="['Ghostable pricing', 'Ghostable Desktop license', 'Git environment management pricing']"
    />
@endpush

<x-layouts.guest title="Ghostable Desktop pricing" canonical="{{ route('pricing') }}" :show-promo-banner="false">
    <section class="relative overflow-hidden bg-zinc-950 px-6 pb-20 pt-20 text-white lg:px-8 lg:pb-28 lg:pt-28">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(70,185,168,0.18),transparent_38%)]"></div>
        <div class="relative mx-auto max-w-4xl text-center">
            <div class="inline-flex items-center rounded-full border border-brand/30 bg-brand/10 px-3 py-1.5 text-sm font-medium text-brand-light">
                Simple, one-time licensing
            </div>
            <h1 class="mt-6 text-5xl font-medium tracking-[-0.055em] text-balance sm:text-6xl lg:text-7xl">
                Own your environment workflow.
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg/8 text-zinc-300 sm:text-xl/8">
                Choose a Ghostable Desktop license once. Every license includes the Git-controlled V3 workflow and one year of product updates.
            </p>
            <div class="mt-7 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-zinc-400">
                <span class="inline-flex items-center gap-1.5"><flux:icon.check class="size-4 text-brand" /> No subscription</span>
                <span class="inline-flex items-center gap-1.5"><flux:icon.check class="size-4 text-brand" /> No account required</span>
                <span class="inline-flex items-center gap-1.5"><flux:icon.check class="size-4 text-brand" /> Secure Stripe checkout</span>
            </div>
        </div>
    </section>

    <section class="bg-zinc-100 px-6 py-20 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            @if($errors->any())
                <div class="mx-auto mb-8 max-w-xl">
                    <flux:callout variant="danger" icon="exclamation-triangle" heading="Checkout could not start">
                        {{ $errors->first() }}
                    </flux:callout>
                </div>
            @endif

            <div class="grid items-stretch gap-6 lg:grid-cols-3">
                @foreach($offers as $offer)
                    <div class="relative flex flex-col rounded-3xl border bg-white p-7 shadow-sm {{ ($offer['featured'] ?? false) ? 'border-brand ring-2 ring-brand/20' : 'border-zinc-200' }} sm:p-8">
                        @if($offer['featured'] ?? false)
                            <flux:badge color="teal" size="sm" class="absolute right-6 top-6">Most popular</flux:badge>
                        @endif

                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-brand-dark">{{ $offer['name'] }}</p>
                            <div class="mt-5 flex items-end gap-2">
                                <span class="text-5xl font-semibold tracking-[-0.05em] text-zinc-950">{{ $offer['price'] }}</span>
                                <span class="pb-1.5 text-sm text-zinc-500">one-time</span>
                            </div>
                            <p class="mt-5 min-h-20 text-base/7 text-zinc-600">{{ $offer['description'] }}</p>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-zinc-100 p-3">
                                <p class="text-xs uppercase tracking-wide text-zinc-500">People</p>
                                <p class="mt-1 text-sm font-semibold text-zinc-950">{{ $offer['seats'] }}</p>
                            </div>
                            <div class="rounded-xl bg-zinc-100 p-3">
                                <p class="text-xs uppercase tracking-wide text-zinc-500">Devices</p>
                                <p class="mt-1 text-sm font-semibold text-zinc-950">{{ $offer['devices'] }}</p>
                            </div>
                        </div>

                        <ul class="mt-7 flex-1 space-y-3 text-sm text-zinc-700">
                            @foreach($offer['features'] as $feature)
                                <li class="flex items-start gap-2.5">
                                    <flux:icon.check-circle class="mt-0.5 size-4 shrink-0 text-brand-dark" />
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-8">
                            @auth
                                <flux:button
                                    href="{{ route('organization.settings.billing') }}"
                                    variant="{{ ($offer['featured'] ?? false) ? 'primary' : 'filled' }}"
                                    class="w-full justify-center">
                                    Choose {{ $offer['name'] }}
                                </flux:button>
                            @else
                                <form method="POST" action="{{ route('licenses.checkout.start', ['plan' => $offer['plan']]) }}">
                                    @csrf
                                    <flux:button
                                        type="submit"
                                        variant="{{ ($offer['featured'] ?? false) ? 'primary' : 'filled' }}"
                                        class="w-full justify-center">
                                        Buy {{ $offer['name'] }}
                                    </flux:button>
                                </form>
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 flex flex-col items-center justify-between gap-5 rounded-2xl border border-zinc-200 bg-white px-6 py-5 text-center sm:flex-row sm:text-left">
                <div>
                    <p class="font-semibold text-zinc-950">Already own a license?</p>
                    <p class="mt-1 text-sm text-zinc-600">Use your purchase email to view keys, seats, devices, and update eligibility.</p>
                </div>
                <flux:button href="{{ route('licenses.manage') }}" variant="ghost" icon:trailing="arrow-right">
                    Manage licenses
                </flux:button>
            </div>
        </div>
    </section>

    <section class="bg-white px-6 py-20 lg:px-8 lg:py-28">
        <div class="mx-auto grid max-w-7xl gap-14 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-dark">Included with every license</p>
                <h2 class="mt-4 text-4xl font-medium tracking-[-0.04em] text-zinc-950 text-balance sm:text-5xl">
                    The complete V3 desktop workflow.
                </h2>
                <p class="mt-5 text-lg/8 text-zinc-600">
                    Plans change capacity, not the core product. Every license includes serverless, Git-controlled environment management.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach([
                    ['Git-controlled configuration', 'Keep encrypted environment definitions alongside the project they configure.'],
                    ['Trusted desktop client', 'Encrypt, decrypt, validate, and materialize configuration locally.'],
                    ['Background synchronization', 'Stay aligned with repository changes without manual copy-and-paste.'],
                    ['One year of updates', 'Receive every Ghostable Desktop update released during the included year.'],
                ] as [$title, $body])
                    <div class="rounded-2xl border border-zinc-200 p-6">
                        <flux:icon.check-circle class="size-5 text-brand-dark" />
                        <h3 class="mt-4 font-semibold text-zinc-950">{{ $title }}</h3>
                        <p class="mt-2 text-sm/6 text-zinc-600">{{ $body }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-zinc-100 px-6 py-20 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-3xl">
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-dark">Questions</p>
                <h2 class="mt-4 text-4xl font-medium tracking-[-0.04em] text-zinc-950">License details</h2>
            </div>

            <div class="mt-10 divide-y divide-zinc-200 rounded-2xl border border-zinc-200 bg-white px-6 sm:px-8">
                @foreach([
                    ['Do I need a Ghostable account?', 'No. You can purchase, receive, recover, and use a license with only the purchase email. An account is optional for broader team management.'],
                    ['What happens after one year?', 'Your license keeps working with the versions released during your update window. Renewing updates is separate from the original one-time license.'],
                    ['Can I move a team seat?', 'Yes. Team licenses include reassignable seats so access can follow the people doing the work.'],
                    ['Where are environment secrets stored?', 'Ghostable V3 keeps encrypted environment material in your Git repository. Plaintext is handled by the trusted local client, not the server.'],
                ] as [$question, $answer])
                    <div class="py-6">
                        <h3 class="font-semibold text-zinc-950">{{ $question }}</h3>
                        <p class="mt-2 text-sm/6 text-zinc-600">{{ $answer }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <flux:button href="{{ route('download') }}" variant="primary" icon="arrow-down-tray">
                    Download
                </flux:button>
            </div>
        </div>
    </section>
</x-layouts.guest>
