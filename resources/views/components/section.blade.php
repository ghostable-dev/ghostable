<section>
    <flux:card class="!bg-black/2 p-3">
        @if(isset($title) || isset($subheading) || isset($actions))
            <div class="flex items-center justify-between gap-8 pb-4 pt-2 px-3">
                <div>
                    @isset($title)
                        <flux:heading size="lg">{{ $title }}</flux:heading>
                    @endisset
                    @isset($subheading)
                        <flux:subheading>{{ $subheading }}</flux:subheading>
                    @endisset
                </div>
                @isset($actions)
                    <div class="flex items-center gap-4">
                        {{ $actions }}
                    </div>
                @endisset
            </div>
        @endif
        @if(isset($form))
            <div class="pb-4 pt-2 px-3">
                {{ $form }}
            </div>
        @endif
        <flux:card>
            {{ $slot }}
        </flux:card>
    </flux:card>
</section>