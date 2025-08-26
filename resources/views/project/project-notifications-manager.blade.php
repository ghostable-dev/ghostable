<section class="space-y-6">
    @php $organization = $this->project->owningOrganization(); @endphp
    @if($organization->slack_enabled && $organization->slack_webhook_url)
        @include('core.slack-enabled-message')
    @endif
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Notification</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($this->notificationOptions as $case)
                <flux:table.row wire:key="proj-notify-{{ $case->value }}">
                    <flux:table.cell>{{ $case->label() }}</flux:table.cell>
                    <flux:table.cell>{{ $case->description() }}</flux:table.cell>
                    <flux:table.cell inset="top bottom" align="end">
                        <flux:switch
                            wire:click="toggle('{{ $case->value }}')"
                            :checked="$this->project->notifications->{$case->value} ?? false"/>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
