<flux:breadcrumbs>
    
    {{-- Project dropdown --}}
    <flux:breadcrumbs.item separator="slash">
        <div class="flex">
            <flux:button variant="ghost" inset="left">
                    {{ $this->environment->project->name }}
                </flux:button>
            <flux:dropdown>
                <flux:button icon="chevron-up-down" variant="subtle"/>
                <flux:navmenu>
                    @foreach ($this->environment->project->team->projects as $project)
                        <flux:navmenu.item
                            href="{{ route('projects.view', $project) }}">
                            {{ $project->name }}
                        </flux:navmenu.item>
                    @endforeach
                </flux:navmenu>
            </flux:dropdown>
        </div>
    </flux:breadcrumbs.item>

    {{-- Environment dropdown --}}
    <flux:breadcrumbs.item>
        <div class="flex">
            <flux:button 
                href="{{ route('environment.view', $this->environment) }}" 
                variant="ghost">
                {{ $this->environment->name }}
            </flux:button>
            <flux:dropdown>
                <flux:button icon="chevron-up-down" variant="subtle"/>
                <flux:navmenu>
                    @foreach ($this->environment->project->environments as $env)
                        <flux:navmenu.item
                            href="{{ route('environment.view', $env) }}">
                            {{ $env->name }}
                        </flux:navmenu.item>
                    @endforeach
                </flux:navmenu>
            </flux:dropdown>
        </div>
    </flux:breadcrumbs.item>
    
</flux:breadcrumbs>