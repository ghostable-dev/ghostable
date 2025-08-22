@section('title', 'Ghostable Blog')

@push('meta')
<x-core.seo-meta
    title="Ghostable Blog"
    description="Product updates, best practices, and tips for managing environment variables with Ghostable."
    :keywords="['ghostable', 'environment variables', 'best practices']"/>
@endpush

<div class="px-6 lg:px-8 py-16 bg-white">
    <div class="mx-auto max-w-2xl lg:max-w-7xl">
        <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
            Ghostable Blog
        </h1>
        <p class="mt-6 max-w-3xl text-2xl font-medium text-gray-500">Stay informed with product updates, company news, and insights on managing environment configuration.</p>

        @if($featuredPosts->isNotEmpty())
            <h2 class="mt-16 text-2xl font-medium tracking-tight">Featured</h2>
            <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-3">
                @foreach($featuredPosts as $post)
                    <x-blog.featured-post-card :post="$post" />
                @endforeach
            </div>
        @endif

        <div class="mt-16">
            @foreach($posts as $post)
                <x-blog.post-card :post="$post" />
            @endforeach

            <div class="mt-6">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</div>
