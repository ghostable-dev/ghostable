@props([
    'token'  
])
<li {{ $attributes }} class="relative flex items-center justify-between py-5">
    <div class="min-w-0 space-y-2">
        <flux:heading class="!text-bold" size="lg">
            {{ $token->name }}
        </flux:heading>
        <flux:subheading size="lg">
            Ending with **********<strong>{{ $token->token_suffix }}</strong>
        </flux:subheading>
        <flux:text>
            Created:
            <time datetime="{{ $token->created_at->toIso8601String() }}">
                {{ $token->created_at->timezone(timezone())->diffForHumans() }}
            </time>
        </flux:text>
    </div>
    <div class="space-y-2">
        
        {{-- Expires at --}}
        <div>
            <flux:badge color="green">
                Expires:&nbsp;
                <time datetime="{{ $token->expires_at->toIso8601String() }}">
                    {{ $token->expires_at->timezone(timezone())->format(DT_FORMAT) }}
                </time>
            </flux:badge>
        </div>
        
        {{-- Last used at --}}
        <div>
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
    
    @isset($menu)
        <div class="space-y-2">
            <flux:dropdown position="left">
                <flux:button icon="ellipsis-vertical" variant="ghost"/>
                <flux:menu>
                    {{ $menu }}
                </flux:menu>
            </flux:dropdown>
        </div>
    @endisset
</li>