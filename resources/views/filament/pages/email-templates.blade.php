<x-filament-panels::page>
    <div class="space-y-4">
        <div class="gap-4">
            <select class="mb-2" wire:model.live="notificationClass">
                @foreach($this->notifications as $section => $classes)
                    <optgroup label="{{ strtoupper($section) }}">
                        @foreach($classes as $class)
                        <option value="{{ $class }}">{{ class_basename($class) }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @if($notificationClass === \App\Messaging\Mail\Broadcast\PostPublishedMailable::class)
                <select class="mb-2" wire:model.live="previewPostId">
                    @foreach($this->publishedPosts as $post)
                        <option value="{{ $post->id }}">{{ $post->title }}</option>
                    @endforeach
                </select>
            @endif
            @if(in_array($notificationClass, [
                \App\Messaging\Mail\Drip\OrganizationSetupNudgeMailable::class,
                \App\Messaging\Mail\Drip\CliSetupNudgeMailable::class,
                \App\Messaging\Mail\Drip\InviteMembersNudgeMailable::class
            ]))
                <div class="flex">
                    <input type="checkbox" id="as_reminder" wire:model.live="as_reminder" checked />
                    <label for="as_reminder">As Reminder</label>
                </div>
            @endif
        </div>
        @if ($this->html)
            <iframe 
                height="800px" 
                width="100%" 
                srcdoc="{{ $this->html }}">        
            </iframe>
        @else
            <p class="text-sm text-gray-500">No preview available.</p>
        @endif
    </div>
</x-filament-panels::page>
