@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

<x-layouts.guest title="Your Ghostable license is ready">
    <section class="bg-white px-6 py-16 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-2xl">
            <flux:card class="p-8 md:p-10">
                <div class="flex size-12 items-center justify-center rounded-full bg-green-100 text-green-700">
                    <flux:icon.check class="size-6" />
                </div>

                <flux:heading size="xl" class="mt-6">Your license is ready</flux:heading>
                <flux:text class="mt-3">
                    We also emailed this key to the address used at checkout. Copy it into Ghostable Desktop to activate {{ $license->plan->label() }}.
                </flux:text>

                <div class="mt-8">
                    <flux:input
                        label="License key"
                        value="{{ $licenseKey }}"
                        readonly
                        copyable
                    />
                </div>

                <flux:callout icon="information-circle" color="blue" class="mt-8">
                    <flux:callout.heading>No account required</flux:callout.heading>
                    <flux:callout.text>
                        Your key works immediately. Saving it to an account is optional and helps with team management and future recovery.
                    </flux:callout.text>
                </flux:callout>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <flux:button href="{{ $claimUrl }}" variant="primary">
                        Save to an account
                    </flux:button>
                    <flux:button href="{{ route('licenses') }}" variant="ghost">
                        Back to licenses
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </section>
</x-layouts.guest>
