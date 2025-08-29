@props(['post'])
<div class="relative grid grid-cols-1 border-b border-b-gray-100 py-10 first:border-t first:border-t-gray-200 max-sm:gap-3 sm:grid-cols-3">
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
            <a href="{{ route('blog.view', $post) }}" class="flex items-center gap-1 text-sm font-medium">
                Read more
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 fill-gray-400"><path fill-rule="evenodd" d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 1 1-1.06-1.06L8.94 8 6.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </div>
</div>
