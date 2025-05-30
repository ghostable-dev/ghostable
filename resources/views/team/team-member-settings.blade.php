<section class="w-full">
    
    @include('team.partials.team-settings-header')

    <x-layouts.team-settings>
        
        <livewire:team.livewire.team-members-manager/>
        
        @if(!$this->team->isPersonal())
            <livewire:team.livewire.team-invites-manager/>
        @endif
        
    </x-layouts.team-settings>
</section>
