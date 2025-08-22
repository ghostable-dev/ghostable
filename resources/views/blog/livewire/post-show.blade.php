@section('title', $post->meta_title)

@push('meta')
<x-core.seo-meta
    :image="!is_null($post->social) ? route('s3.asset', $post->social) : null"
    type="article"
    :title="$post->meta_title"
    :description="$post->meta_description"
    :keywords="$post->meta_keywords"/>
@endpush

<div class="bg-white">
    @if($post->hero)
        <div class="h-64 w-full bg-cover bg-center" style="background-image: url({{ route('s3.asset', $post->hero) }});"></div>
    @endif

    <div class="px-6 py-12 lg:px-8">
        <div class="mx-auto max-w-xl text-base leading-7 text-gray-700">
            @include('partials.blog.post-details')
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                {{ $post->title }}
            </h1>
            <p class="mt-6 text-xl leading-8">
                {{ $post->description }}
            </p>
            <div class="prose mt-10 max-w-2xl">
                {!! $post->renderedContent() !!}
            </div>
        </div>
    </div>

    {{-- <livewire:account.livewire.mailing-list-signup-form/> --}}
</div>
