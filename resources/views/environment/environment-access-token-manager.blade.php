<div class="space-y-6">
    
    <x-section>
        <x-slot:title>CLI Tokens</x-slot:title>
        <x-slot:subheading>Environment-scoped tokens allow for CI/CD pipeline access.</x-slot:subheading>
        <x-slot:actions>
            <flux:modal.trigger name="create-token">
                <flux:button 
                    variant="primary"
                    icon:trailing="plus">
                    New CLI Token
                </flux:button>
            </flux:modal.trigger>
        </x-slot:actions>
        @if(count($this->tokens))
            <ul role="list" class="space-y-3 w-full divide-y">
                @foreach($this->tokens as $token)
                    <x-environment.token-list-item :token="$token" wire:key="token-li-{{ $token->id }}">
                        <x-slot:menu>
                            <flux:menu.item>
                                <x-env-token-expiry-reminder :token="$token"/>
                            </flux:menu.item>
                            <x-auth.confirms-password wire:then="remove('{{ $token->id }}')">
                                <flux:menu.item>
                                    Remove<span class="sr-only"> token, {{ $token->name }}</span>
                                </flux:menu.item>
                            </x-auth.confirms-password>
                        </x-slot:menu>
                    </x-environment.token-list-item>
                @endforeach
            </ul>
        @else
            <flux:callout.heading>No tokens</flux:callout.heading>
            <flux:callout.text>You haven't created any tokens yet.</flux:callout.text>
        @endif
    </x-section>

    {{-- New token modal form --}}
    <flux:modal name="create-token" class="md:w-lg">
        <form wire:submit.prevent="create" class="space-y-6">
            <div class="space-y-4">
                <flux:heading size="lg">New CLI Token</flux:heading>
                <flux:subheading>
                    This will generate a read-only token for the 
                    <flux:text variant="strong" class="inline">{{ $this->environment->name }}</flux:text> environment.
                </flux:subheading>
            </div>
            <div class="space-y-4">
                <flux:input label="Name" placeholder="e.g. github-deploy-key" wire:model.defer="name" required />
                <flux:select label="Expires After" variant="listbox" wire:model.defer="expires_after">
                    <flux:select.option :value="7">7 Days</flux:select.option>
                    <flux:select.option :value="30">30 Days</flux:select.option>
                    <flux:select.option :value="90">90 Days</flux:select.option>
                </flux:select>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    Generate Token
                </flux:button>
            </div>
        </form>
    </flux:modal>
    
    {{-- Show token modal --}}
    <flux:modal name="show-token" class="md:w-xl" :dismissible="false">
        <div class="space-y-6">
            <div class="space-y-4">
                <flux:heading size="lg">Token Successfully Created</flux:heading>
                <flux:subheading>
                    Warning make sure you copy the below token now. We don't store it
                    and you will not be able to see it again.
                </flux:subheading>
            </div>
            <div>
                <flux:input 
                    value="{{ $this->newToken['token'] ?? 'TBD' }}" 
                    description="This token will expire on {{ $this->newToken['expires'] ?? 'TBD' }}."
                    readonly 
                    copyable/>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="primary" 
                    x-on:click="
                        $flux.modal('show-token').close();
                        $wire.set('newToken', null);
                    ">
                    Yes, I copied it.
                </flux:button>
            </div>
        </div>
    </flux:modal>
    
</div>