@section('title', $post->meta_title)

@push('meta')
<x-core.seo-meta
    :image="!is_null($post->social) ? route('s3.asset', $post->social) : null"
    type="article"
    :title="$post->meta_title"
    :description="$post->meta_description"
    :keywords="$post->meta_keywords"/>
@endpush

<div class="px-6 lg:px-8 bg-white py-10">
    <div class="mx-auto max-w-2xl lg:max-w-7xl">
        <h2 class="mt-16 font-mono text-xs/5 font-semibold tracking-widest text-gray-500 uppercase">
            {{ $post->posted_at->isoFormat('dddd, MMMM D, YYYY') }}
        </h2>

        <h1 class="mt-2 text-4xl font-medium tracking-tighter text-pretty text-gray-950 sm:text-6xl">
            {{ $post->title }}
        </h1>

        <div class="mt-16 grid grid-cols-1 gap-8 pb-24 lg:grid-cols-[15rem_1fr] xl:grid-cols-[15rem_1fr_15rem]">
            <div class="flex flex-wrap items-center gap-8 max-lg:justify-between lg:flex-col lg:items-start">
                {{-- <div class="flex items-center gap-3">
                    <img alt="" class="aspect-square size-6 rounded-full object-cover" src="{{ asset('images/crypto-dark.svg') }}">
                    <div class="text-sm/5 text-gray-700">Ghostable Team</div>
                </div> --}}

                <div class="flex flex-wrap gap-2">
                    <a
                        class="rounded-full border border-dotted border-gray-300 bg-gray-50 px-2 text-sm/6 font-medium text-gray-500"
                        href="{{ route('blog.index', ['category' => $post->category->value]) }}">
                        {{ $post->category->label() }}
                    </a>
                </div>
            </div>

            <div class="text-gray-700">
                <div class="max-w-2xl xl:mx-auto">
                    @if($post->hero)
                        <img
                            alt="{{ $post->title }}"
                            class="mb-10 aspect-3/2 w-full rounded-2xl object-cover shadow-xl"
                            src="{{ $post->hero }}">
                    @endif

                    @if($post->description)
                        <p class="my-10 text-base/8 first:mt-0 last:mb-0">
                            {{ $post->description }}
                        </p>
                    @endif

                    <div class="prose my-10 max-w-2xl">
                        {!! $post->renderedContent() !!}
                    </div>

                    <div class="mt-10">
                        <flux:button icon="chevron-left" href="{{ route('blog.index') }}">
                            Back to blog
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
