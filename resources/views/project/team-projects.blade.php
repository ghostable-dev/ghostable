<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Projects') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your environment variables.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    
    <flux:modal.trigger name="create-project">
        <flux:button variant="primary">
            Create New Project
        </flux:button>
    </flux:modal.trigger>
    
    <ul role="list" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
        @foreach($this->projects as $project)
            <li class="col-span-1" wire:key="project-{{ $project->id }}">
                <flux:callout icon="circle-stack">
                    <flux:callout.heading>
                        <flux:link href="{{ route('projects.view', $project) }}">{{ $project->name }}</flux:link>
                    </flux:callout.heading>
                    <flux:callout.text>
                        Select an environment from below.
                    </flux:callout.text>
                    <x-slot name="actions">
                        @foreach($project->environments as $env)
                            <flux:link href="{{ route('environment.view', $env) }}">{{ $env->name }}</flux:link>
                        @endforeach
                    </x-slot>
                </flux:callout>
            </li>
        @endforeach
    </ul>
    
    <livewire:project.livewire.project-create-modal/>
    
</section>
