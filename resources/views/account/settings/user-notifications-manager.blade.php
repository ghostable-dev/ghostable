<div class="max-w-xl">
    <x-section 
        submit="updateNotifications" 
        title="{{ __('Notifications') }}"
        subheading="{{ __('Manage your email notification settings.') }}">
        <div class="space-y-6">
            <div class="col-span-6 sm:col-span-4">
                <flux:switch 
                    label="Promotional"
                    description="Receive updates on special promotions, product updates, and community benefits." 
                    id="promotional" 
                    name="promotional" 
                    wire:model.live="promotional"
                    align="left">
                </flux:switch>
            </div>
            <div class="col-span-6 sm:col-span-4">
                <flux:switch 
                    label="Blog & Newsletter"
                    description="Receive updates when new blog posts are published."
                    id="blog" 
                    name="blog" 
                    wire:model.live="blog"
                    align="left">
                </flux:switch>
            </div>
        </div>
    </x-section>
</div>