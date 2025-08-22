<x-layouts.environment :environment="$this->environment">
    <div>
        
        @if($this->environment->owningTeam()->features->audits)
            @can('viewAuditLogs', $this->environment->owningTeam())
                <x-section>
                    <x-slot:title>Activity History</x-slot:title>
                    <x-slot:subheading>
                        <div class="max-w-2xl">
                            View a timeline of changes and access events for this environment. Every update, override, and deployment action is recorded for visibility and traceability.
                        </div>
                    </x-slot:subheading>
                    @if(count($this->activities))
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
                        <flux:callout.heading>No activities</flux:callout.heading>
                        <flux:callout.text>No activities have been recorded yet.</flux:callout.text>
                    @endif
                </x-section>
            @else
                <x-access-restricted/>
            @endcan
            @else
                <x-non-personal-team-restricted/>
            @endif
        
    </div>
</x-layouts.environment>