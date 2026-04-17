<section class="w-full" data-screenshot-ready="dashboard-projects">
    <div class="relative mb-6 w-full -mt-2">
        <flux:heading size="xl" level="1">{{ __('All Projects') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Browse and manage your organization\'s projects.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    
    @can('create', [\App\Project\Models\Project::class, $this->organization])
    <flux:modal.trigger name="create-project">
        <flux:button variant="primary" class="mb-4">
            Create New Project
        </flux:button>
    </flux:modal.trigger>
    @endcan
    
    @if($this->projects->count())
        <ul role="list" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3 auto-rows-fr">
            @foreach($this->projects as $project)
                <li class="col-span-1" wire:key="project-{{ $project->id }}">
                    <flux:callout icon="circle-stack" class="h-full min-h-[180px] flex flex-col">
                        <flux:callout.heading>
                            <flux:link href="{{ route('project.environments', $project) }}">{{ $project->name }}</flux:link>
                        </flux:callout.heading>
                        <flux:callout.text>
                            Select an environment from below.
                        </flux:callout.text>
                        <x-slot name="actions">
                            @php
                                $environments = $project->environments;
                                $total = $environments->count();
                            @endphp
                            
                            <div class="flex flex-wrap gap-2">
                                @foreach($environments->take(4) as $env)
                                    <flux:link href="{{ route('environment.variables', $env) }}">
                                        {{ str()->limit($env->name, 15) }}
                                    </flux:link>
                                @endforeach

                                @if($total > 4)
                                    <flux:link>
                                        @php $remaining = $total - 4; @endphp
                                        and {{ $remaining }} {{ str()->plural('other', $remaining) }}
                                    </flux:link>
                                @endif
                            </div>
                        </x-slot>
                    </flux:callout>
                </li>
            @endforeach
        </ul>
    @else
        <div class="space-y-6">
            <flux:heading size="md">{{ __('No projects yet') }}</flux:heading>
            <flux:subheading>{{ __('Get started and create your first project.') }}</flux:subheading>
        </div>
    @endif
    
    <livewire:project.livewire.project-create-modal/>
    
</section>
