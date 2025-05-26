<section class="w-full">
    @include('team.partials.team-settings-header')

    <x-layouts.team-settings>
        <div>
            <flux:heading size="lg">{{ __('Team Owner') }}</flux:heading>
            <div class="flex items-center gap-3 mt-4">
                <flux:profile
                    :initials="$this->team->owner->initials()"
                    :chevron="false"
                    circle/>
                <span>
                    <b class="block text-black dark:text-white">{{ $this->team->owner->name }}</b>
                    {{ $this->team->owner->email }}
                </span>
                
            </div>
        </div>
        
        <div>
            <div>
                <flux:heading size="lg">{{ __('Team Name') }}</flux:heading>
                <flux:subheading size="lg">{{ __('Update your team\'s name.') }}</flux:subheading>
            </div>
            
            <form wire:submit="updateTeamName" class="my-6 w-full space-y-6">
                <flux:input wire:model="name" label="Name" :readonly="!$this->canEditName" required/>
                <div class="flex items-center gap-4">
                    @if($this->canEditName)
                        <div class="flex items-center justify-end">
                            <flux:button variant="primary" type="submit" class="w-full">
                                {{ __('Save') }}
                            </flux:button>
                        </div>
                    @endif
                    <x-action-message class="me-3" on="name-updated">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>
        </div>
    </x-layouts.team-settings>
</section>
