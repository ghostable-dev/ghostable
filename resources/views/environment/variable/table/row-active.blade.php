<flux:table.row wire:key="var-{{ $var }}">
    <flux:table.cell>
        @if($var->inherited)
            <flux:tooltip content="{{ $var->origin }}">
                <flux:button variant="subtle" icon="git-branch" size="xs" class="!text-brand"/>
            </flux:tooltip>
        @elseif($var->is_override)
            <flux:tooltip content="{{ $this->environment->base->name }}">
                <flux:button variant="subtle" icon="arrow-path" size="xs" class="!text-brand"/>
            </flux:tooltip>
        @endif
    </flux:table.cell>
    <flux:table.cell>
        <flux:text size="sm">{{ $var->key }}</flux:text>
    </flux:table.cell>
    <flux:table.cell>
        @php
            $secret = $var->isSecret();
            $showingSecret = $this->showing[$var->id] ?? null;
        @endphp
        <flux:input 
            value="{{ $showingSecret ? $var->value : $var->displayValue() }}"
            :copyable="!$secret || $showingSecret"
            readonly>
            @if($secret)
                <x-slot name="iconTrailing">
                    <flux:button 
                        wire:click="toggleSecret('{{ $var->id }}')" 
                        size="sm" 
                        variant="subtle" 
                        icon="{{ !$showingSecret ? 'eye' : 'eye-slash'}}" 
                        class="-mr-1" />
                </x-slot>
            @endif
        </flux:input>
    </flux:table.cell>
    <flux:table.cell>
        {{ $var->latestVersion->version }}
    </flux:table.cell>
    <flux:table.cell>
        {{ $var->last_updated_at->shortAbsoluteDiffForHumans() }}
    </flux:table.cell>
    <flux:table.cell align="end">
        @if($this->canEditVariables)
            <flux:dropdown position="left">
                <flux:button variant="ghost" icon="ellipsis-vertical"></flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="editVariable('{{ $var->id }}')">
                        Edit
                    </flux:menu.item>
                    <flux:menu.item wire:click="removeVariable('{{ $var->id }}')" variant="danger">
                        Delete
                    </flux:menu.item>
                    <flux:menu.separator/>
                    <flux:menu.item wire:click="viewVariableActivity('{{ $var->id }}')">
                        Activity
                    </flux:menu.item>
                    @if($var->latestVersion->version > 1)
                        <flux:menu.item wire:click="viewVersions('{{ $var->id }}')">
                            Versions
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        @endif
    </flux:table.cell>
</flux:table.row>