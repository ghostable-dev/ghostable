<section class="space-y-12 pb-10">
    
    <flux:card class="space-y-4 bg-zinc-100/20">
        <flux:heading size="lg">
            <span class="inline-flex items-center gap-2">
                <flux:icon.slack/>
                Slack Notifications
            </span>
        </flux:heading>
        <flux:subheading>
            Send team notifications to Slack. All notifications for projects,
            environments, and secrets will also be routed to this endpoint.
        </flux:subheading>
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
    </flux:card>
    
    <flux:fieldset>
        <div class="space-y-4">
            @foreach($this->teamNotificationOptions as $notification)
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
</section>
