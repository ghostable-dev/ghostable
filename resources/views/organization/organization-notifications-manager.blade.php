<div class="space-y-6 max-w-4xl">

    <x-section>
        <x-slot:title>
            <span class="inline-flex items-center gap-2">
                <flux:icon.slack/>
                Slack Notifications
            </span>
        </x-slot:title>
        <x-slot:subheading>
            Send organization notifications to Slack. All notifications for projects,
            environments, and secrets will also be routed to this endpoint.
        </x-slot:subheading>
        <div>
            <flux:switch
                align="left"
                label="Enabled"
                wire:model.live="slack_enabled"/>
        </div>
        @if($slack_enabled)
            <form wire:submit="saveSlackSettings" class="space-y-4">
                <flux:input
                    label="Slack Webhook URL"
                    type="url"
                    wire:model="slack_webhook_url"/>
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full">
                            {{ __('Save') }}
                        </flux:button>
                    </div>
                    <x-action-message class="me-3" on="slack-webhook-updated">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>
        @endif
    </x-section>

    <x-section>
        <x-slot:title>{{ __('Notifications') }}</x-slot:title>
        <x-slot:subheading>
            {{ __('Choose which notifications you\'d like to receive for this organization.') }}
        </x-slot:subheading>
        <flux:fieldset>
            <div class="space-y-4">
                @foreach($this->organizationNotificationOptions as $notification)
                    <flux:switch
                        wire:click="toggle('{{ $notification['key'] }}')"
                        :checked="$notification['enabled']"
                        label="{{ $notification['label'] }}"
                        description="{{ $notification['description'] }}"/>

                    @if (!$loop->last)
                        <flux:separator variant="subtle" />
                    @endif
                @endforeach
            </div>
        </flux:fieldset>
    </x-section>

</div>
