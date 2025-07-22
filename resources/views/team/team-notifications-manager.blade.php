<section class="space-y-6">
    <div>
        <flux:heading size="lg">Slack Notifications</flux:heading>
        <flux:subheading>Send team notifications to Slack.</flux:subheading>
        <form wire:submit="saveSlackSettings" class="my-6 w-full space-y-6">
            <flux:input type="text" label="Slack Webhook URL" wire:model.defer="slackWebhookUrl"/>
            <div class="flex items-center gap-4">
                <flux:switch wire:click="toggleSlackEnabled" :checked="$slackEnabled"/>
                <flux:button variant="primary" type="submit">Save</flux:button>
                <x-action-message class="me-3" on="slack-settings-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </div>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Notification</flux:table.column>
            <flux:table.column>Description</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach(\App\Team\Notifications\TeamNotification::cases() as $case)
                <flux:table.row wire:key="team-notify-{{ $case->value }}">
                    <flux:table.cell>{{ $case->label() }}</flux:table.cell>
                    <flux:table.cell>{{ $case->description() }}</flux:table.cell>
                    <flux:table.cell inset="top bottom" align="end">
                        <flux:switch
                            wire:click="toggle('{{ $case->value }}')"
                            :checked="$this->team->notifications->{$case->value} ?? false"/>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
