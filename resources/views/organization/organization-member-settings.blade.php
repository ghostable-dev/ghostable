<section class="w-full">
    
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        
        <livewire:organization.livewire.organization-members-manager/>
        
        @if(!$this->organization->isPersonal())
            <livewire:organization.livewire.organization-invites-manager/>
        @endif
        
    </x-layouts.organization-settings>
</section>
