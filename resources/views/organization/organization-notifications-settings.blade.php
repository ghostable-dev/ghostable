<section class="w-full" data-screenshot-ready="org-notifications-settings">
    @include('organization.partials.organization-settings-header')
    <x-layouts.organization-settings>
        <div class="space-y-6">
            <livewire:organization.livewire.organization-notifications-manager />
            <livewire:organization.livewire.organization-audit-webhooks-manager />
        </div>
    </x-layouts.organization-settings>
</section>
