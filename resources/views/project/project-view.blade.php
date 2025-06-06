<section class="w-full space-y-6">
    
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ $this->project->name }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Create environments, override permissions, and manage project-level settings.') }}
        </flux:subheading>
    </div>
    
    <flux:tab.group>
        
        <flux:tabs wire:model="tab">
            <flux:tab name="environments">Environments</flux:tab>
            <flux:tab name="general">General</flux:tab>
            <flux:tab name="access">Access</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="environments">
            <livewire:project.livewire.project-environments-manager :project="$this->project"/>
        </flux:tab.panel>
        
        <flux:tab.panel name="general">
            @perform($this->project, 'project:manage-settings')
                <livewire:project.livewire.project-general-settings :project="$this->project"/>
            @else
                <x-access-restricted/>
            @endperform
        </flux:tab.panel>
        
        <flux:tab.panel name="access">
            @can('manageAccessControls', $this->project->team)
                <livewire:project.livewire.project-access-manager :project="$this->project"/>
            @else
                <x-access-restricted/>
            @endcan
        </flux:tab.panel>
        
    </flux:tab.group>
    
</section>
