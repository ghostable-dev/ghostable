<flux:table.row wire:key="rule-{{ $rule->id }}">
    {{-- Origin / Override indicator --}}
    <flux:table.cell>
        @if($rule->inherited)
            <flux:tooltip content="{{ $rule->origin }}">
                <flux:button variant="subtle" icon="git-branch" size="xs" class="!text-brand" />
            </flux:tooltip>
        @elseif($rule->is_override)
            <flux:tooltip content="{{ $this->environment->base->name }}">
                <flux:button variant="subtle" icon="arrow-path" size="xs" class="!text-brand" />
            </flux:tooltip>
        @endif
    </flux:table.cell>

    {{-- Key --}}
    <flux:table.cell>
        <flux:text>{{ $rule->key }}</flux:text>
    </flux:table.cell>

    {{-- Type & Constraints --}}
    <flux:table.cell>
        <div class="flex items-center gap-1 mb-1">
            <flux:badge variant="secondary" size="sm">{{ $rule->type->label() }}</flux:badge>
            @if($rule->is_required)
                <flux:badge color="red" size="sm">Required</flux:badge>
            @endif
        </div>
        <flux:text size="xs" class="text-gray-500">
            @switch($rule->type->value)
                @case('string')
                    Length: {{ $rule->min ?? 0 }}–{{ $rule->max ?? '∞' }}
                    @break

                @case('integer')
                    Value: {{ $rule->min  ?? '–∞' }}–{{ $rule->max  ?? '∞' }}
                    @break

                @case('enum')
                    Allowed: {{ implode(', ', $rule->allowed_values) }}
                    @break
            @endswitch
        </flux:text>
    </flux:table.cell>

    {{-- Description --}}
    <flux:table.cell>
        @if($rule->description)
            <flux:text size="sm">
                {{ \Illuminate\Support\Str::limit($rule->description, 50) }}
            </flux:text>
        @else
            <flux:text size="xs" class="text-gray-400 italic">—</flux:text>
        @endif
    </flux:table.cell>

    {{-- Actions --}}
    <flux:table.cell align="end">
        @if($this->canEditVariables)
            <flux:dropdown position="left">
                <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="editRule('{{ $rule->id }}')">
                        Edit
                    </flux:menu.item>
                    <flux:menu.item
                        wire:click="removeRule('{{ $rule->id }}')"
                        variant="danger">
                        Delete
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endif
    </flux:table.cell>
</flux:table.row>
