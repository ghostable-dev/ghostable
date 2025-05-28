@props([
    'title' => __('Confirm Password'), 
    'content' => __('For your security, please confirm your password to continue.'), 
    'button' => __('Confirm')
])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<span
    {{ $attributes->wire('then') }}
    x-data
    x-ref="span"
    x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
    x-on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);"
>
    {{ $slot }}
</span>

@once
<flux:modal wire:model.self="confirmingPassword" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $title }}</flux:heading>
            <flux:text class="mt-2">{{ $content }}</flux:text>
        </div>
        <div>
            <flux:input 
                type="password" 
                placeholder="{{ __('Password') }}" 
                autocomplete="current-password"
                x-ref="confirmable_password"
                wire:model="confirmablePassword"
                wire:keydown.enter="confirmPassword" />
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button 
                dusk="confirm-password-button" 
                wire:click="confirmPassword" 
                variant="primary">
                {{ $button }}
            </flux:button>
        </div>
    </div>
</flux:modal>
@endonce