<section class="space-y-6">
    @if($this->team && $this->team->slack_enabled && $this->team->slack_webhook_url)
        <flux:callout icon="slack" variant="ghost" inline>
            <flux:callout.heading>Slack Notifications Enabled</flux:callout.heading>
            <flux:callout.text>
                Notifications for this secret will also be sent to Slack via the parent team.
            </flux:callout.text>
        </flux:callout>
    @endif
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Notification</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach(\App\Secret\Notifications\SecretNotification::cases() as $case)
                <flux:table.row wire:key="secret-notify-{{ $case->value }}">
                    <flux:table.cell>{{ $case->label() }}</flux:table.cell>
                    <flux:table.cell>{{ $case->description() }}</flux:table.cell>
                    <flux:table.cell inset="top bottom" align="end">
                        <flux:switch
                            wire:click="toggle('{{ $case->value }}')"
                            :checked="$this->secret->notifications->{$case->value} ?? false"/>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
