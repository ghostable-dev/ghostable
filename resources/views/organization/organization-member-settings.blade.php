<section class="w-full" data-screenshot-ready="org-members-settings">
    @include('organization.partials.organization-settings-header')

    <x-layouts.organization-settings>
        <div class="space-y-6" data-screenshot-frame="org-members-and-invites">
            <livewire:organization.livewire.organization-members-manager/>

            <livewire:organization.livewire.invites-manager/>
        </div>
    </x-layouts.organization-settings>
</section>
