<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Projects') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your various projects.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <flux:table>
    <flux:table.columns>
        <flux:table.column>Name</flux:table.column>
        <flux:table.column>Created</flux:table.column>
        <flux:table.column>Environments</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach($this->projects as $project)
            <flux:table.row wire:key="project-{{ $project->id }}">
                <flux:table.cell>{{ $project->name }}</flux:table.cell>
                <flux:table.cell>{{ $project->created_at }}</flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm" inset="top bottom">
                        {{ $project->environments()->count() }}
                    </flux:badge>
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
</section>
