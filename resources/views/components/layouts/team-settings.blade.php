<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item :href="route('team.settings.general')" wire:navigate>
                {{ __('General') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('team.settings.members')" wire:navigate>
                {{ __('Members') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('team.settings.notifications')" wire:navigate>
                Notifications
            </flux:navlist.item>
            <!-- <flux:navlist.item :href="route('team.settings.billing')" wire:navigate>
                {{ __('Billing') }}
            </flux:navlist.item> -->
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <div class="mt-5 w-full max-w-2xl space-y-12">
            {{ $slot }}
        </div>
    </div>
</div>
