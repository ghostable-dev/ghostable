<div class="space-y-6">
    
    <x-section>
        <x-slot:title>Deploy Tokens</x-slot:title>
        <x-slot:subheading>Environment-scoped deploy tokens for CI/CD pipelines. Tokens are created via the API/CLI and can be reviewed or revoked here.</x-slot:subheading>
        <x-slot:actions></x-slot:actions>
        @if(count($this->tokens))
            <ul role="list" class="space-y-3 w-full divide-y">
                @foreach($this->tokens as $token)
                    <x-environment.token-list-item :token="$token" wire:key="token-li-{{ $token->id }}">
                        <x-slot:menu>
                            <flux:menu.item>
                                <x-env-token-expiry-reminder :token="$token"/>
                            </flux:menu.item>
                            <flux:menu.item wire:click="remove('{{ $token->id }}')">
                                Remove<span class="sr-only"> token, {{ $token->name }}</span>
                            </flux:menu.item>
                        </x-slot:menu>
                    </x-environment.token-list-item>
                @endforeach
            </ul>
        @else
            <flux:callout.heading>No tokens</flux:callout.heading>
            <flux:callout.text>Create deploy tokens via the Ghostable API/CLI. Issued tokens will appear here for visibility and revocation.</flux:callout.text>
        @endif
    </x-section>
    
</div>
