@section('title', $post->meta_title)

@push('meta')
<x-core.seo-meta
    :image="!is_null($post->social) ? route('s3.asset', $post->social) : null"
    type="article"
    :title="$post->meta_title"
    :description="$post->meta_description"
    :keywords="$post->meta_keywords"/>
@endpush

{{-- @push('scripts')
    <x-schema.blog-posting :post="$post"/>
@endpush --}}

<div class="bg-black">
    
    <div 
        x-data="{ opacity: 0.80 }" 
        x-init="window.addEventListener('scroll', () => {
          const newOpacity = Math.max(0, .80 - window.pageYOffset / 400);
          opacity = newOpacity;
        })"
        @if($post->hero)
        :style="{
          'background-image': 'url({{ route('s3.asset', $post->hero) }})',
          'will-change': 'transform',
          'opacity': opacity
        }" 
        @endif
        class="flex items-center justify-center bg-fixed bg-center py-28">
        &nbsp;
    </div>

    <div class="bg-white px-6 py-12 lg:px-8">
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