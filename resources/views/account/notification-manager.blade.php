<div class="flex flex-col sm:justify-center items-center">
    <div class="py-12 text-center space-y-12">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
            <span class="flex h-10 w-auto mb-3 items-center justify-center rounded-md">
                <img class="fill-current text-white" src="{{ asset('images/logo-light.svg') }}"/>
            </span>
            <span class="sr-only">Ghostable</span>
        </a>
        <div>
            <span class="text-base font-medium leading-7 text-brand">
                Email Preferences
            </span>
            <h2 class="mt-2 text-2xl font-medium text-pretty tracking-tight text-white sm:text-4xl">
                Manage your email preferences
            </h2>
        </div>
        <div class="dark px-6 max-w-lg">
            <div class="text-left">
                <div class="space-y-10">
                    
                    <div>
                        <flux:heading class="text-brand">{{ $this->notifiable->email }}</flux:heading>
                        <flux:subheading>Checked preferences indicate the type of emails you are opted into</flux:subheading>
                    </div>
        
                    <flux:switch 
                        id="promotional" 
                        name="promotional" 
                        wire:model.defer="promotional" 
                        label="Promotional"
                        description="Receive updates on special promotions, product updates, and community benefits.">
                    </flux:switch>
                    
                    <flux:switch 
                        id="blog" 
                        name="blog" 
                        wire:model.defer="blog" 
                        label="Blog & Newsletter"
                        description="Receive updates when new blog posts are published.">
                    </flux:switch>
                    
                    <div class="flex justify-center">
                        <flux:button 
                            wire:click="save"
                            wire:target="save"
                            wire:loading.attr="disabled"
                            variant="primary">
                            Update preferences
                        </flux:button>
                    </div>
                    
                </div>
            </div>
        </div>
</div>
</div>