@section('title', 'Ghostable Blog')

@push('meta')
<x-core.seo-meta
    title="Ghostable Blog"
    description="Product updates, best practices, and tips for managing environment variables with Ghostable."
    :keywords="['ghostable', 'environment variables', 'best practices']"/>
@endpush

<div class="bg-white py-16 px-4">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-center text-4xl font-bold tracking-tight text-gray-900">Ghostable Blog</h1>
        <p class="mt-4 text-center text-lg text-gray-600">Product updates, tutorials, and ideas for keeping your environment variables in sync.</p>

        <div class="mt-16 space-y-12">
            @foreach($posts as $post)
                <article class="flex flex-col gap-4 lg:flex-row">
                    <div class="relative aspect-[16/9] lg:w-52 lg:shrink-0">
                        @if($post->hero)
                            <img src="{{ route('s3.asset', $post->hero) }}"
                                 alt="{{ $post->title }}"
                                 class="absolute inset-0 h-full w-full rounded-lg object-cover"/>
                        @endif
                    </div>
                    <div class="group relative">
                        @include('partials.blog.post-details')
                        <h2 class="mt-1 text-2xl font-bold leading-7 text-gray-900 group-hover:text-primary">
                            <a href="{{ route('blog.view-post', $post) }}">
                                <span class="absolute inset-0"></span>
                                {{ $post->title }}
                            </a>
                        </h2>
                        <p class="mt-3 text-base leading-6 text-gray-600">{{ $post->description }}</p>
                        <div class="mt-4">
                            <a href="{{ route('blog.view-post', $post) }}" class="text-base font-semibold text-primary hover:text-primary-light">Read full story &rarr;</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-16">
            {{ $posts->links() }}
        </div>
    </div>
</div>
