@props([
    'token'  
])
<li {{ $attributes }}>
    <flux:card>
        
        <div class="flex flex-wrap items-center justify-between sm:flex-nowrap">
            <div class="space-y-4">
                <div>
                    <flux:heading class="!text-bold" size="lg">
                        {{ $token->name }}
                    </flux:heading>
                    <flux:subheading size="lg">
                        Token ending with **********<strong>{{ $token->token_suffix }}</strong>
                    </flux:subheading>
                </div>
                <div class="flex items-center sm:flex-row gap-x-2 ">
                    
                    {{-- Created At --}}
                    <flux:text>
                        Created:
                        <time datetime="{{ $token->created_at->toIso8601String() }}">
                            {{ $token->created_at->timezone(timezone())->diffForHumans() }}
                        </time>
                    </flux:text>
                    
                    <flux:separator vertical class="hidden sm:block"/>
                    
                    {{-- Expires at --}}
                    <flux:badge color="green">
                        Expires:&nbsp;
                        <time datetime="{{ $token->expires_at->toIso8601String() }}">
                            {{ $token->expires_at->timezone(timezone())->format(DT_FORMAT) }}
                        </time>
                    </flux:badge>
                    
                    <flux:separator vertical class="hidden sm:block"/>
                    
                    {{-- Last used at --}}
                    @if($token->last_used_at)
                        <flux:badge>
                            Last Used:
                            <time datetime="{{ $token->last_used_at->toIso8601String() }}">
                                {{ $token->last_used_at->timezone(timezone())->diffForHumans() }}
                            </time>
                        </flux:badge>
                    @else
                        <flux:badge color="yellow" icon="exclamation-triangle">Last Used: Never</flux:badge>
                    @endif
                    
                </div>
            </div>
            <div class="flex flex-none items-center gap-x-4">
                {{ $actions }}
            </div>
        </div>
    </flux:card>
</li>