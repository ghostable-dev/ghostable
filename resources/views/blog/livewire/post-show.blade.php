@php
    use App\Blog\Enums\PostType;

    $isInsight = $post->type?->is(PostType::INSIGHT);
@endphp

@push('meta')
<x-seo-meta
    :image="!is_null($post->social) ? Storage::url($post->social) : null"
    :type="($post->type?->is(\App\Blog\Enums\PostType::ARTICLE) ?? false) ? 'article' : 'website'"
    :title="$post->meta_title ?? $post->title"
    :description="$post->meta_description ?? $post->description"
    :keywords="$post->meta_keywords"/>
<x-blog-posting-schema :post="$post"/>
<x-breadcrumb-schema :items="[
    ['name' => 'Blog', 'item' => route('blog.index')],
    ['name' => $isInsight ? 'Insights' : 'Articles', 'item' => $isInsight ? route('blog.insights') : route('blog.articles')],
    ['name' => $isInsight ? 'Insights' : $post->category?->label(), 'item' => $isInsight ? route('blog.insights') : route('blog.category', $post->category)],
    ['name' => $post->title, 'item' => route('blog.view', $post)],
]" />
@endpush

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/github-dark.min.css">
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/languages/bash.min.js"></script>
    <script>
      hljs.highlightAll();
    </script>
@endpush

<div>
    <div class="px-6 lg:px-8 bg-white pt-10">
        <div class="mt-4 mx-auto max-w-2xl lg:max-w-7xl">
            @if($isInsight)
                <div class="grid grid-cols-1 gap-8 pb-24 lg:grid-cols-[15rem_1fr] xl:grid-cols-[15rem_1fr_15rem]">
                    <div class="flex flex-wrap items-start gap-8 max-lg:justify-between lg:flex-col lg:items-start">
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-gray-900 px-3 py-1 text-xs font-semibold text-white">Insight</span>
                        </div>
                    </div>

                    <div class="text-gray-800">
                        <div class="max-w-2xl xl:mx-auto">
                            <div class="mb-10">
                                <div>
                                    <flux:breadcrumbs class="pb-6">
                                        <flux:breadcrumbs.item href="{{ route('blog.index') }}" separator="slash">Blog</flux:breadcrumbs.item>
                                        <flux:breadcrumbs.item href="{{ route('blog.insights') }}">Insights</flux:breadcrumbs.item>
                                    </flux:breadcrumbs>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    @if($post->posted_at)
                                        <span class="font-mono text-xs/5 font-semibold tracking-widest text-gray-500 uppercase">
                                            {{ $post->posted_at->isoFormat('dddd, MMMM D, YYYY') }}
                                        </span>
                                    @endif
                                </div>
                                <h1 class="mt-3 text-4xl font-semibold tracking-tighter text-pretty text-accent sm:text-5xl">
                                    {{ $post->title }}
                                </h1>
                            </div>

                            @if($post->description)
                                <p class="my-10 text-xl text-pretty text-accent/80 first:mt-0 last:mb-0">
                                    {{ $post->description }}
                                </p>
                            @endif

                            @if($post->hero)
                                <div class="-mt-8 overflow-hidden rounded-3xl border border-white/40 bg-white shadow-xl shadow-gray-900/10">
                                    <img
                                        alt="{{ $post->title }}"
                                        class="w-full object-cover"
                                        src="{{ Storage::url($post->hero) }}">
                                </div>
                            @endif

                            <div class="prose my-10 max-w-none prose-lg prose-slate">
                                {!! $post->renderedContent() !!}
                            </div>

                            <div class="mt-10 flex items-center justify-between">
                                <flux:button icon="chevron-left" href="{{ route('blog.index') }}">
                                    Back to blog
                                </flux:button>
                                <flux:link href="{{ route('blog.insights') }}">All insights</flux:link>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-8 pb-24 lg:grid-cols-[15rem_1fr] xl:grid-cols-[15rem_1fr_15rem]">
                    <div class="flex flex-wrap items-center gap-8 max-lg:justify-between lg:flex-col lg:items-start">
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-gray-900 px-3 py-1 text-xs font-semibold text-white">Article</span>
                        </div>
                    </div>

                    <div class="text-gray-700">
                        <div class="max-w-2xl xl:mx-auto">
                            <div>
                                <flux:breadcrumbs class="pb-6">
                                    <flux:breadcrumbs.item href="{{ route('blog.index') }}" separator="slash">Blog</flux:breadcrumbs.item>
                                    <flux:breadcrumbs.item href="{{ route('blog.articles') }}">Articles</flux:breadcrumbs.item>
                                    <flux:breadcrumbs.item href="{{ route('blog.category', $post->category) }}">
                                        {{ $post->category->label() }}
                                    </flux:breadcrumbs.item>
                                </flux:breadcrumbs>
                            </div>

                            <div class="mb-10">
                                <div class="flex flex-wrap items-center gap-3">
                                    @if($post->posted_at)
                                        <span class="font-mono text-xs/5 font-semibold tracking-widest text-gray-500 uppercase">
                                            {{ $post->posted_at->isoFormat('dddd, MMMM D, YYYY') }}
                                        </span>
                                    @endif
                                </div>
                                <h1 class="mt-3 text-4xl font-medium tracking-tighter text-pretty text-gray-950 sm:text-5xl">
                                    {{ $post->title }}
                                </h1>
                            </div>

                            @if($post->description)
                                <p class="my-10 text-xl text-pretty text-base/8 first:mt-0 last:mb-0">
                                    {{ $post->description }}
                                </p>
                            @endif

                            @if($post->hero)
                                <img
                                    alt="{{ $post->title }}"
                                    class="mb-10 aspect-3/2 w-full rounded-2xl object-cover shadow-xl"
                                    src="{{ Storage::url($post->hero) }}">
                            @endif

                            <div class="prose my-10 max-w-2xl">
                                {!! $post->renderedContent() !!}
                            </div>

                            <div class="mt-10 flex items-center justify-between">
                                <flux:button icon="chevron-left" href="{{ route('blog.index') }}">
                                    Back to blog
                                </flux:button>
                                <flux:link href="{{ route('blog.articles') }}">All articles</flux:link>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <livewire:account.livewire.mailing-list-signup-form/>
</div>
