<section class="w-full space-y-6">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ $this->project->name }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your environment variables.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    
    <flux:modal.trigger name="create-env">
        <flux:button variant="primary">
            Create New Environment
        </flux:button>
    </flux:modal.trigger>
    
    <ul role="list" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
        @foreach($this->project->environments as $env)
            <li class="col-span-1" wire:key="env-{{ $env->id }}">
                <flux:callout icon="circle-stack">
                    <flux:callout.heading>{{ $env->name }}</flux:callout.heading>
                    <flux:callout.text>
                        Select an environment from below.
                    </flux:callout.text>
                    <x-slot name="actions">
                        <flux:link href="{{ route('environment.view', $env) }}">View</flux:link>
                    </x-slot>
                </flux:callout>
            </li>
        @endforeach
    </ul>

    <livewire:environment.livewire.environment-create-modal :project="$this->project"/>
    
</section>
