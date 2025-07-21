
{{-- Breadcrumbs: Project -> Environment --}}
<x-slot name="breadcrumbs">
    <flux:breadcrumbs.item separator="slash">
        <x-projects-drop-button :project="$this->environment->project"/>
    </flux:breadcrumbs.item>
    <flux:breadcrumbs.item>
        <x-environments-drop-button :environment="$this->environment"/>
    </flux:breadcrumbs.item>
</x-slot>

<section class="space-y-6">
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
    
    {{-- <flux:avatar.group class="mt-6">
        @foreach($this->environment->project->team->users as $user)
            <flux:avatar circle size="xs" :initials="$user->initials()" />
        @endforeach
    </flux:avatar.group> --}}
    
    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="variables">Variables</flux:tab>
            <flux:tab name="secrets">Secrets</flux:tab>
            <flux:tab name="validation">Validation</flux:tab>
            <flux:tab name="general">General</flux:tab>
            <flux:tab name="access">Access</flux:tab>
            <flux:tab name="activity">Activity</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="variables">
            @perform($this->environment, 'var:view')
                <livewire:environment.livewire.environment-variable-manager
                :environment="$this->environment"/>
            @else
                <x-access-restricted/>
            @endperform
        </flux:tab.panel>

        <flux:tab.panel name="secrets">
            @perform($this->environment, 'secret:view')
                <livewire:secret.livewire.secrets-manager :owner="$this->environment"/>
            @else
                <x-access-restricted/>
            @endperform
        </flux:tab.panel>
        
        <flux:tab.panel name="validation">
            @perform($this->environment, 'var:manage-rules')
                <livewire:environment.validation.livewire.variable-rule-manager 
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
            @if(!$this->environment->owningTeam()->isPersonal())
                @can('manageAccessControls', $this->environment->owningTeam())
                    <div class="space-y-6">
                        <livewire:environment.livewire.environment-access-token-manager 
                            :environment="$this->environment"/>
                        <livewire:environment.livewire.environment-access-manager 
                            :environment="$this->environment"/>
                    </div>
                @else
                    <x-access-restricted/>
                @endcan
            @else
                <x-non-personal-team-restricted/>
            @endif
        </flux:tab.panel>
        
        <flux:tab.panel name="activity">
            @if(!$this->environment->owningTeam()->isPersonal())
                @can('viewAuditLogs', $this->environment->owningTeam())
                    <livewire:environment.livewire.environment-activity 
                        :environment="$this->environment"/>
                @else
                    <x-access-restricted/>
                @endcan
            @else
                <x-non-personal-team-restricted/>
            @endif
        </flux:tab.panel>
        
    </flux:tab.group>
    
</section>