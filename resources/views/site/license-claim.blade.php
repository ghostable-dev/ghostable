@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

<x-layouts.guest title="Save your Ghostable license">
    <section class="bg-white px-6 py-16 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-xl">
            <flux:card class="p-8 md:p-10">
                <div class="flex size-12 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                    <flux:icon.key class="size-6" />
                </div>

                <flux:heading size="xl" class="mt-6">Save your {{ $license->plan->label() }} license</flux:heading>
                <flux:text class="mt-3">
                    The license is already active and does not require an account. Claim it only if you want account-based management and recovery.
                </flux:text>

                @auth
                    @if($license->purchaser_user_id === auth()->id())
                        <flux:callout icon="check-circle" color="green" class="mt-8">
                            <flux:callout.heading>Already saved</flux:callout.heading>
                            <flux:callout.text>This license already belongs to your account.</flux:callout.text>
                        </flux:callout>

                        <flux:button href="{{ route('organization.settings.billing') }}" variant="primary" class="mt-8 w-full">
                            View license
                        </flux:button>
                    @else
                        <form method="POST" action="{{ $claimUrl }}" class="mt-8">
                            @csrf
                            <flux:button type="submit" variant="primary" class="w-full">
                                Save license to my account
                            </flux:button>
                        </form>

                        <flux:text size="sm" class="mt-4 text-center">
                            You must use the verified email address entered during Stripe checkout.
                        </flux:text>
                    @endif
                @else
                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                        <flux:button href="{{ route('register') }}" variant="primary" class="w-full">
                            Create account
                        </flux:button>
                        <flux:button href="{{ route('login') }}" variant="filled" class="w-full">
                            Sign in
                        </flux:button>
                    </div>

                    <flux:text size="sm" class="mt-4 text-center">
                        Use the same email address you entered during Stripe checkout.
                    </flux:text>
                @endauth
            </flux:card>
        </div>
    </section>
</x-layouts.guest>
