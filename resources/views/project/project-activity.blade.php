<x-layouts.project :project="$this->project">
    
    @if($this->project->owningOrganization()->hasPaidPlan())
        @can('viewAuditLogs', $this->project->owningOrganization())
            <flux:table :paginate="$this->activities">
                <flux:table.columns>
                    <flux:table.column>Event</flux:table.column>
                    <flux:table.column>Subject</flux:table.column>
                    <flux:table.column>User</flux:table.column>
                    <flux:table.column>Source</flux:table.column>
                    <flux:table.column>Time</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->activities as $activity)
                        <flux:table.row>
                            <flux:table.cell>{{ ucfirst($activity->event) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm">{{ $activity->subject_type }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($activity->causer)
                                    <flux:profile circle 
                                        :chevron="false"
                                        size="xs" 
                                        :initials="$activity->causer->initials()"
                                        name="{{ $activity->causer->email }}"/>
                                @else
                                    System
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $activity->description }}</flux:table.cell>
                            <flux:table.cell>{{ $activity->created_at->timezone(timezone())->diffForHumans() }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <x-access-restricted/>
        @endcan
    @else
        <x-paid-plan-required/>
    @endif
        
</x-layouts.project>
