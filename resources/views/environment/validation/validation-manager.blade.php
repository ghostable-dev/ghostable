<x-layouts.environment-settings :environment="$this->environment">
    
    <div class="space-y-6 max-w-4xl">
    
        <x-section>
            <x-slot:title>Validation</x-slot:title>
            <x-slot:subheading>
                <div class="max-w-2xl">
                    Validation rules help enforce that critical environment variables 
                    are present and correctly configured. If validation fails, Ghostable 
                    can block CI deployments to protect your pipelines.
                </div>
            </x-slot:subheading>
            <x-slot:actions>
                <flux:button 
                    variant="primary"
                    wire:click="launchCreateRuleModal"
                    icon:trailing="plus">Add Rule</flux:button>
            </x-slot:actions>
            @if(count($this->rules))
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column></flux:table.column>
                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'key'"
                            :direction="$sortDirection"
                            wire:click="sort('key')">Key</flux:table.column>
                        <flux:table.column>Rule</flux:table.column>
                        <flux:table.column>Description</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->rules as $rule)
                            @if ($rule->is_deleted)
                                @include('environment.validation.table.row-tombstoned')
                            @else
                                @include('environment.validation.table.row-active')
                            @endif
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:callout.heading>No rules</flux:callout.heading>
                <flux:callout.text>You haven't created any rules yet.</flux:callout.text>
            @endif
        </x-section>
        
        {{-- Rule creator modal --}}
        <livewire:environment.validation.livewire.variable-rule-creator :environment="$this->environment"/>
            
        {{-- Rule editor modal --}}
        <livewire:environment.validation.livewire.variable-rule-editor/>

        {{-- Rule deleter modal --}}
        <livewire:environment.validation.livewire.variable-rule-deleter />

        {{-- Rule reinstater modal --}}
        <livewire:environment.validation.livewire.variable-rule-reinstater />
        
    </div>

</x-layouts.environment-settings>
