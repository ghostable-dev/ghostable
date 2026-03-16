<section class="w-full" data-screenshot-ready="org-members-settings">
    
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        <div class="space-y-6" data-screenshot-frame="org-members-and-invites">
            <livewire:organization.livewire.organization-members-manager/>

            <livewire:organization.livewire.invites-manager/>
        </div>

        <livewire:organization.livewire.organization-key-reshare-requests-manager/>
        
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
