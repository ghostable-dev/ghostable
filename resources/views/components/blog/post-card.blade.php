@props(['post'])
@php
    use App\Blog\Enums\PostType;

    $isInsight = $post->type?->is(PostType::INSIGHT);
@endphp

<div>
    @if($post->posted_at)
        <div class="text-sm max-sm:text-gray-700 sm:font-medium">
            {{ $post->posted_at->isoFormat('dddd, MMMM D, YYYY') }}
        </div>
    @endif
    <div class="mt-2.5 flex items-center gap-2">
        <span class="inline-flex items-center gap-2 rounded-full bg-gray-900 px-3 py-1 text-xs font-semibold text-white">
            {{ $post->type->label() }}
        </span>
        @if(! $isInsight && $post->category)
            <div class="text-sm text-gray-700">{{ $post->category->label() }}</div>
        @endif
    </div>
</div>
<div class="sm:col-span-2 sm:max-w-2xl">
    <h2 class="text-lg font-semibold text-gray-900">{{ $post->title }}</h2>
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
