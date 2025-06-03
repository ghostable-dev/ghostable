<section class="w-full space-y-6">
    
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ $this->project->name }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your environment variables.') }}</flux:subheading>
    </div>
    
    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="environments">Environments</flux:tab>
            <flux:tab name="general">General</flux:tab>
            <flux:tab name="access">Access</flux:tab>
            {{-- <flux:tab name="activity">Activity</flux:tab> --}}
            {{-- <flux:tab name="integrations">Integrations</flux:tab> --}}
        </flux:tabs>

        <flux:tab.panel name="environments">
            <flux:modal.trigger name="create-env">
                <flux:button variant="primary" class="mb-4">
                    Create New Environment
                </flux:button>
            </flux:modal.trigger>
            <livewire:environment.livewire.environment-create-modal :project="$this->project"/>
            <ul role="list" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                @foreach($this->project->environments as $env)
                    <li class="col-span-1" wire:key="env-{{ $env->id }}">
                        <flux:callout>
                            <flux:callout.heading>
                                <flux:badge size="sm" class="mb-2">
                                    {{ $env->type->label() }}
                                </flux:badge>
                            </flux:callout.heading>
                            <flux:callout.heading>{{ $env->name }}</flux:callout.heading>
                            <x-slot name="actions">
                                <flux:link href="{{ route('environment.view', $env) }}">View</flux:link>
                            </x-slot>
                        </flux:callout>
                    </li>
                @endforeach
            </ul>
        </flux:tab.panel>
        
        <flux:tab.panel name="general">
            <livewire:project.livewire.project-general-settings :project="$this->project"/>
        </flux:tab.panel>
        
        <flux:tab.panel name="access">
            <livewire:project.livewire.project-access-manager :project="$this->project"/>
        </flux:tab.panel>
        
    </flux:tab.group>
    
</section>
