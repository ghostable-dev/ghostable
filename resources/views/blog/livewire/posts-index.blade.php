@push('meta')
    <x-seo-meta
        title="{{ $this->metaTitle }}"
        description="{{ $this->metaDescription }}"
        :keywords="$this->metaKeywords"
    />
@endpush

<div class="px-6 lg:px-8 py-16 bg-white">
    <div class="mx-auto max-w-2xl lg:max-w-7xl">
        
        @isset($this->category)
            <flux:breadcrumbs class="pb-6">
                <flux:breadcrumbs.item href="{{ route('blog.index') }}" separator="slash">Blog</flux:breadcrumbs.item>
                <flux:breadcrumbs.item separator="slash">{{ $this->category->label() }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        @endisset
      
        <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
            @isset($this->category)
                {{ $this->category->label() }}
            @else
                Ghostable Blog
            @endif
        </h1>
        <p class="mt-6 max-w-3xl text-2xl font-medium text-gray-500">
            @isset($this->category)
                {{ $this->category->description() }}
            @else
                Stay informed with product updates, company news, and insights on managing environment configuration.
            @endif
        </p>

        @if(is_null($this->category) && $this->featured->isNotEmpty())
            <h2 class="mt-16 text-2xl font-medium tracking-tight">Featured</h2>
            <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-3">
                @foreach($this->featured as $post)
                    <x-blog.featured-post-card :post="$post" />
                @endforeach
            </div>
        @endif
        
        <div class="mt-16">
            @if(count($this->posts))
                <div>
                    @foreach($this->posts as $post)
                        <x-blog.post-card :post="$post" />
                    @endforeach
                    <div class="mt-6">
                        {{ $this->posts->links() }}
                    </div>
                </div>
            @else
                <div class="space-y-6">
                    <flux:heading size="lg" level="3">No posts found</flux:heading>
                    <flux:subheading>We don’t have any articles published here yet.</flux:subheading>
                    <flux:button variant="primary" icon="chevron-left" href="{{ route('blog.index') }}">
                        Back to blog
                    </flux:button>
                </div>
            @endif
        </div>

    </div>
</div>
