<div class="bg-zinc-50">
    <div class="py-16 sm:py-24 lg:py-32">
        @if(!$submitted)
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-6 lg:grid-cols-12 lg:gap-8 lg:px-8">
            <div 
                class="max-w-xl text-3xl font-medium tracking-tighter text-pretty  text-zinc-900 sm:text-4xl lg:col-span-7">
                <h2 class="inline sm:block lg:inline xl:block">
                    Want product news and updates?
                </h2>
                <p class="inline sm:block lg:inline xl:block">
                    Sign up for our newsletter.
                </p>
            </div>        
            <form wire:submit="save" class="w-full max-w-md lg:col-span-5 lg:pt-2">
                <div class="flex gap-x-4">
                    <x-label for="ml-email-address" class="sr-only">Email Address</x-label>
                    <flux:input 
                        id="ml-email-address"
                        name="email" 
                        type="email" 
                        autocomplete="email" 
                        wire:model="email"
                        placeholder="Enter your email" 
                        required/>
                    <flux:button type="submit" variant="primary"
                        wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                {{ __('Subscribe') }}
                                <span class="ml-1" aria-hidden="true">&rarr;</span>
                            </span>
                            <span wire:loading>
                                <span class="flex inline-flex items-center">
                                    <span>{{ __('Subscribing...') }}</span>
                                </span>
                            </span>
                        </span>
                    </flux:button>
                </div>
                <div class="py-4">
                    <flux:text>We care about your data. Read our <flux:link href="{{ route('privacy') }}">privacy&nbsp;policy</flux:link>.</flux:text>
                </div>
            </form>
        </div>
        @else
            <div class="mx-auto grid max-w-7xl px-6 lg:px-8">
                <flux:heading size="xl" level="2">Thank you for signing up!</flux:heading>
                <flux:subheading size="xl">We appreciate your interest. Stay tuned for the latest updates and exciting news coming your way.</flux:subheading>
            </div>
        @endif
    </div>
</div>