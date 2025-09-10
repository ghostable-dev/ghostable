<x-layouts.project-settings :project="$this->project">
    <div class="space-y-6 max-w-4xl">
        <x-section>
            <x-slot:title>Notifications</x-slot:title>
            <x-slot:subheading>
                <div class="max-w-2xl">
                    Choose which emails you'd like to receive for this project.
                </div>
            </x-slot:subheading>

            @php $organization = $this->project->owningOrganization(); @endphp
            @if($organization->slack_enabled && $organization->slack_webhook_url)
                @include('core.slack-enabled-message')
            @endif

            <flux:fieldset>
                <div class="space-y-4">
                    @foreach($this->notificationOptions as $case)
                        <flux:switch
                            align="left"
                            wire:click="toggle('{{ $case->value }}')"
                            :checked="$this->project->notifications->{$case->value} ?? false"
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
</x-layouts.project-settings>

