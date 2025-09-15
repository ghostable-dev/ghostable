@props(['post'])
    <div>
        <div class="text-sm max-sm:text-gray-700 sm:font-medium">
            {{ $post->posted_at->isoFormat('dddd, MMMM D, YYYY') }}
        </div>
        <div class="mt-2.5 flex items-center gap-3">
            <div class="text-sm text-gray-700">{{ $post->category->label() }}</div>
        </div>
    </div>
    <div class="sm:col-span-2 sm:max-w-2xl">
        <h2 class="text-sm font-medium">{{ $post->title }}</h2>
        <p class="mt-3 text-sm text-gray-500">{{ $post->description }}</p>
        <div class="mt-4">
            <flux:link href="{{ route('blog.view', $post) }}">
                <span class="flex items-center gap-1">
                    Read more
                    <flux:icon.chevron-right variant="micro"/>
                </span>
            </flux:link>
        </div>
    </div>
