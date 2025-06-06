<section class="space-y-6">
    
    @include('environment.partials.environment-breadcrumbs')
    
    <div class="relative w-full">
        <flux:badge size="sm" class="mb-2">
            {{ $this->environment->type->label() }}
        </flux:badge>
        <flux:heading size="xl" level="1">
            {{ $this->environment->project->name }} • 
            <span class="text-gray-400">{{ $this->environment->name }}</span>
        </flux:heading>
        <flux:subheading class="mb-6">
            {{ __('Manage your environment variables.') }}
        </flux:subheading>
    </div>
    
    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="variables">Variables</flux:tab>
            <flux:tab name="general">General</flux:tab>
            <flux:tab name="access">Access</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="variables">
            @perform($this->environment, 'var:view')
                <livewire:environment.livewire.environment-variable-manager 
                :environment="$this->environment"/>
            @else
                <x-access-restricted/>
            @endperform
        </flux:tab.panel>
        
        <flux:tab.panel name="general">
            @perform($this->environment, 'env:manage-settings')
                <livewire:environment.livewire.environment-general-settings 
                    :environment="$this->environment"/>
            @else
                <x-access-restricted/>
            @endperform
        </flux:tab.panel>
        
        <flux:tab.panel name="access">
            @can('manageAccessControls', $this->environment->owningTeam())
                <livewire:environment.livewire.environment-access-manager 
                    :environment="$this->environment"/>
            @else
                <x-access-restricted/>
            @endcan
            
        </flux:tab.panel>
        
    </flux:tab.group>
    
    {{-- <flux:avatar.group class="mt-6">
        @foreach($this->environment->project->team->users as $user)
            <flux:avatar circle size="xs" :initials="$user->initials()" />
        @endforeach
    </flux:avatar.group> --}}
    
</section>