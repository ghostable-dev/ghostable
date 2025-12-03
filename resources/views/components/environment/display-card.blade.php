@props(['env'])

<flux:callout>
    <flux:callout.heading>
        
        
        
        <div>
            {{-- <flux:badge size="sm" class="mb-4">
                {{ $env->type->label() }}
            </flux:badge> --}}

            {{-- Name + Type + Lock badge --}}
            <div class="flex items-center gap-2">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $env->name }}
                </h2>
                @if($env->locked)
                    <flux:badge size="sm" color="red" icon="lock">
                        Locked
                    </flux:badge>
                @endif
            </div>

            {{-- Metadata row --}}
            <div class="mt-1 text-sm text-gray-500 flex flex-wrap gap-x-4">
                @php
                    $secretCount = $env->env_secrets_count ?? $env->envSecrets()->count();
                @endphp

                @if($secretCount > 0)
                    <span>{{ $secretCount }} variables</span>
                @endif

                @isset($env->updated_at)
                    <span>Updated {{ $env->updated_at->timezone(timezone())->diffForHumans() }}</span>
                @endisset
            </div>
        </div>
    </flux:callout.heading>

    {{-- Action slot --}}
    <x-slot name="actions">
        <flux:link href="{{ route('environment.variables', $env) }}">
            View
        </flux:link>
    </x-slot>
</flux:callout>
