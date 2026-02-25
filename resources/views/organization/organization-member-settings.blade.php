<section class="w-full">
    
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        
        <livewire:organization.livewire.organization-members-manager/>

        <livewire:organization.livewire.organization-key-reshare-requests-manager/>
        
        <livewire:organization.livewire.invites-manager/>
        
    </x-layouts.organization-settings>

    @if(request()->query('tab') === 'key-reshare-requests')
        <script>
            window.addEventListener('load', () => {
                document.getElementById('key-reshare-requests')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
            });
        </script>
    @endif
</section>
