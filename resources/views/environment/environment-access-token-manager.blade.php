<div class="space-y-6">
    
    {{-- Current tokens --}}
    <div class="space-y-10">
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-8">
                <div>
                    <flux:heading size="lg">CLI Tokens</flux:heading>
                    <flux:subheading>Environment-scoped tokens allow for CI/CD pipeline access.</flux:subheading>
                </div>
                <div>
                    <flux:modal.trigger name="create-token">
                        <flux:button variant="primary">New CLI Token</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
            <flux:card class="bg-zinc-50 p-3">
                @if(count($this->tokens))
                    <ul role="list" class="space-y-3 w-full">
                        @foreach($this->tokens as $token)
                            <x-environment.token-list-item :token="$token" wire:key="token-li-{{ $token->id }}">
                                <x-slot:actions>
                                    <x-env-token-expiry-reminder :token="$token"/>
                                    <x-auth.confirms-password wire:then="remove('{{ $token->id }}')">
                                        <flux:link>
                                            Remove<span class="sr-only"> token, {{ $token->name }}</span>
                                        </flux:link>
                                    </x-auth.confirms-password>
                                </x-slot:actions>
                            </x-environment.token-list-item>
                        @endforeach
                    </ul>
                @else
                    <flux:callout.heading>No tokens</flux:callout.heading>
                    <flux:callout.text>You haven't created any tokens yet.</flux:callout.text>
                @endif
            </flux:card>
        </div>
    </div>

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