<x-layouts.environment-settings :environment="$this->environment">
    <div class="space-y-6 max-w-4xl">
        <x-section>
            <x-slot:title>Notifications</x-slot:title>
            <x-slot:subheading>
                <div class="max-w-2xl">
                    Choose which emails you'd like to receive for this environment.
                </div>
            </x-slot:subheading>

            @php $team = $this->environment->owningTeam(); @endphp
            @if($team->slack_enabled && $team->slack_webhook_url)
                @include('core.slack-enabled-message')
            @endif

            <flux:fieldset>
                <div class="space-y-4">
                    @foreach($this->notificationOptions as $case)
                        <flux:switch
                            align="left"
                            wire:model.live="notifications.{{ $case->value }}"
                            label="{{ $case->label() }}"
                            description="{{ $case->description() }}"/>

                        @if (! $loop->last)
                            <flux:separator variant="subtle" />
                        @endif
                    @endforeach
                </div>
            </flux:fieldset>
        </x-section>
    </div>
</x-layouts.environment-settings>

