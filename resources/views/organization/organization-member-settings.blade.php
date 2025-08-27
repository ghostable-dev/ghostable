<section class="w-full">
    
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        
        <livewire:organization.livewire.organization-members-manager/>
        
        <livewire:organization.livewire.invites-manager/>
        
    </x-layouts.organization-settings>
</section>
