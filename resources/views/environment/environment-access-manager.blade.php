<x-layouts.environment-settings :environment="$this->environment">
    
    <div class="space-y-12 max-w-4xl">
        
        @if(!$this->environment->owningTeam()->isPersonal())
            @can('manageAccessControls', $this->environment->owningTeam())
                <div class="space-y-6">
                    
                    {{-- CLI Token management --}}
                    <livewire:environment.livewire.environment-access-token-manager 
                        :environment="$this->environment"/>
                    
                    {{-- User Override Access Restrictions --}}
                    <x-section>
                        <x-slot:title>Access Restrictions</x-slot:title>
                        <x-slot:subheading>
                            <div class="max-w-2xl">
                                Limit access to this environment by requiring explicit overrides for non-admins. Team and project permissions will no longer apply.
                            </div>
                        </x-slot:subheading>
                        <x-slot:actions>
                            <flux:switch 
                                align="left" 
                                label="Enabled"
                                wire:model.live="is_restricted" 
                                x-on:change="$flux.modal('confirm-restricted-access').show()" />
                            @if($this->environment->is_restricted)
                                <flux:modal.trigger name="add-override">
                                    <flux:button 
                                        variant="primary"
                                        icon:trailing="plus">
                                        Add Override
                                    </flux:button>
                                </flux:modal.trigger>
                            @endif
                        </x-slot:actions>
                        @if($this->overrides->isNotEmpty() && $this->environment->is_restricted)
                             @include('environment.access.partials.overrides-table')
                        @else
                            <flux:callout.heading>No overrides</flux:callout.heading>
                            <flux:callout.text>You haven't created any overrides yet.</flux:callout.text>
                        @endif
                    </x-section>
                    
                    {{-- Override enablement toggle modal --}}
                    @include('environment.access.partials.override-enablement-toggle-modal')
                    
                    {{-- Override creation modal --}}
                    @include('environment.access.partials.override-creation-modal')

                    {{-- Override removal modal --}}
                    @include('environment.access.partials.override-removal-modal')
                    
                </div>
            @else
                <x-access-restricted/>
            @endcan
        @else
            <x-non-personal-team-restricted/>
        @endif
            
    </div>
</x-layouts.environment-settings>