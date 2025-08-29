@props(['post'])
<article class="relative flex flex-col rounded-3xl bg-white p-2 shadow-md ring-1 shadow-black/5 ring-black/5">
    <div class="aspect-3/2 w-full rounded-2xl bg-gray-200">
        @if($post->hero)
            <img src="{{ $post->hero }}" alt="{{ $post->title }}" class="w-full h-full object-cover rounded-2xl"/>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-8">
        <div class="text-sm text-gray-700">{{ $post->posted_at->isoFormat('dddd, MMMM D, YYYY') }}</div>
        <div class="mt-2 text-base font-medium">
            <a href="{{ route('blog.view', $post) }}">
                <span class="absolute inset-0"></span>{{ $post->title }}
            </a>
        </div>
        <div class="mt-2 flex-1 text-sm text-gray-500">
            {{ $post->description }}
        </div>
        <div class="mt-6 flex items-center gap-3">
            <div class="text-sm text-gray-700">{{ $post->category->label() }}</div>
        </div>
    </div>
</article>
