@push('meta')
    <x-seo-meta
        title="{{ $this->metaTitle }}"
        description="{{ $this->metaDescription }}"
        :keywords="$this->metaKeywords"
    />
@endpush

<div class="bg-white">
    <div class="px-6 lg:px-8 pt-16">
        <div class="mx-auto max-w-2xl lg:max-w-7xl pb-16">
            
            @isset($this->category)
                <flux:breadcrumbs class="pb-6">
                    <flux:breadcrumbs.item href="{{ route('blog.index') }}" separator="slash">Blog</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item separator="slash">{{ $this->category->label() }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @elseif($this->type)
                <flux:breadcrumbs class="pb-6">
                    <flux:breadcrumbs.item href="{{ route('blog.index') }}" separator="slash">Blog</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item separator="slash">{{ \Illuminate\Support\Str::plural($this->typeLabel()) }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            @endisset
          
            <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                @isset($this->category)
                    {{ $this->category->label() }}
                @elseif($this->type)
                    {{ \Illuminate\Support\Str::plural($this->typeLabel()) }}
                @else
                    Ghostable Blog
                @endif
            </h1>
            <p class="mt-6 max-w-3xl text-2xl font-medium text-gray-500">
                @isset($this->category)
                    {{ $this->category->description() }}
                @elseif($this->type?->is(\App\Blog\Enums\PostType::ARTICLE))
                    Deep dives, product updates, and long-form articles from the Ghostable team.
                @elseif($this->type?->is(\App\Blog\Enums\PostType::INSIGHT))
                    Market and industry takes on security and platform trends.
                @else
                    Stay informed with product updates, company news, and industry insights on configuration, security, and platform trends.
                @endif
            </p>
            @if(is_null($this->category) && is_null($this->type) && $this->featured->isNotEmpty())
                <h2 class="mt-16 text-2xl font-medium tracking-tight">Featured</h2>
                <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-3">
                    @foreach($this->featured as $post)
                        <x-blog.featured-post-card :post="$post" />
                    @endforeach
                </div>
            @endif
            
            <div class="mt-16">
                @if(is_null($this->category) && is_null($this->type))
                    @php
                        $hasArticles = $this->articles->isNotEmpty();
                        $hasInsights = $this->insights->isNotEmpty();
                    @endphp

                    @if($hasArticles || $hasInsights)
                        <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
                            @if($hasArticles)
                                <div>
                                    <div class="flex items-center justify-between gap-4">
                                        <h2 class="text-2xl font-medium tracking-tight">Articles</h2>
                                        <flux:link href="{{ route('blog.articles') }}">
                                            All articles
                                        </flux:link>
                                    </div>
                                    <div class="mt-6 space-y-0 divide-y divide-gray-100">
                                        @foreach($this->articles as $post)
                                            <div 
                                                @class([
                                                    'relative grid grid-cols-1 items-center gap-4 rounded-xl bg-white p-4 sm:grid-cols-[140px_1fr]',
                                                ])>
                                                <div class="aspect-[4/3] overflow-hidden rounded-lg bg-gray-100">
                                                    @if($post->social)
                                                        <img
                                                            src="{{ Storage::url($post->social) }}"
                                                            alt="{{ $post->title }}"
                                                            class="h-full w-full object-cover transition duration-200 hover:scale-[1.02]"
                                                        >
                                                    @else
                                                        <div class="flex h-full items-center justify-center text-sm text-gray-400">No image</div>
                                                    @endif
                                                </div>

                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        <span>{{ $post->category->label() }}</span>
                                                        <span aria-hidden="true">•</span>
                                                        <span>{{ $post->posted_at?->format('M j, Y') }}</span>
                                                    </div>
                                                    <h3 class="text-xl font-semibold text-gray-900">
                                                        <a href="{{ route('blog.view', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                                                    </h3>
                                                    <p class="text-sm text-gray-600 line-clamp-3">{{ $post->description }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            @if($hasInsights)
                                <div>
                                    <div class="flex items-center justify-between gap-4">
                                        <h2 class="text-2xl font-medium tracking-tight">Insights</h2>
                                        <flux:link href="{{ route('blog.insights') }}">
                                            All insights
                                        </flux:link>
                                    </div>
                                    <div class="mt-6 space-y-0 divide-y divide-gray-100">
                                        @foreach($this->insights as $post)
                                            <div 
                                                @class([
                                                    'relative grid grid-cols-1 items-center gap-4 rounded-xl bg-white p-4 sm:grid-cols-[140px_1fr]',
                                                ])>
                                                <div class="aspect-[4/3] overflow-hidden rounded-lg bg-gray-100">
                                                    @if($post->social)
                                                        <img
                                                            src="{{ Storage::url($post->social) }}"
                                                            alt="{{ $post->title }}"
                                                            class="h-full w-full object-cover transition duration-200 hover:scale-[1.02]"
                                                        >
                                                    @else
                                                        <div class="flex h-full items-center justify-center text-sm text-gray-400">No image</div>
                                                    @endif
                                                </div>

                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        <span>{{ $post->category->label() }}</span>
                                                        <span aria-hidden="true">•</span>
                                                        <span>{{ $post->posted_at?->format('M j, Y') }}</span>
                                                    </div>
                                                    <h3 class="text-xl font-semibold text-gray-900">
                                                        <a href="{{ route('blog.view', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                                                    </h3>
                                                    <p class="text-sm text-gray-600 line-clamp-3">{{ $post->description }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="space-y-6">
                            <flux:heading size="lg" level="3">No posts found</flux:heading>
                            <flux:subheading>We don’t have any articles or insights published here yet.</flux:subheading>
                            <flux:button variant="primary" icon="chevron-left" href="{{ route('blog.index') }}">
                                Back to blog
                            </flux:button>
                        </div>
                    @endif
                @elseif($this->posts->count())
                    <div class="space-y-0 divide-y divide-gray-100">
                        @foreach($this->posts as $post)
                            <div 
                                @class([
                                    'relative grid grid-cols-1 items-center gap-4 py-6 sm:grid-cols-[120px_1fr]',
                                ])>
                                <div class="aspect-[4/3] overflow-hidden rounded-lg bg-gray-100">
                                    @if($post->social)
                                        <img
                                            src="{{ Storage::url($post->social) }}"
                                            alt="{{ $post->title }}"
                                            class="h-full w-full object-cover transition duration-200 hover:scale-[1.02]"
                                        >
                                    @else
                                        <div class="flex h-full items-center justify-center text-sm text-gray-400">No image</div>
                                    @endif
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <span>{{ $post->category->label() }}</span>
                                        <span aria-hidden="true">•</span>
                                        <span>{{ $post->posted_at?->format('M j, Y') }}</span>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900">
                                        <a href="{{ route('blog.view', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                                    </h3>
                                    <p class="text-sm text-gray-600 line-clamp-3">{{ $post->description }}</p>
                                </div>
                            </div>
                        @endforeach
                        <div class="my-6">
                            {{ $this->posts->links() }}
                        </div>
                    </div>
                @else
                    @php
                        $emptyLabel = $this->type
                            ? \Illuminate\Support\Str::plural($this->typeLabel())
                            : ($this->category ? "{$this->category->label()} posts" : 'articles');
                    @endphp
                    <div class="space-y-6">
                        <flux:heading size="lg" level="3">No posts found</flux:heading>
                        <flux:subheading>We don’t have any {{ strtolower($emptyLabel) }} published here yet.</flux:subheading>
                        <flux:button variant="primary" icon="chevron-left" href="{{ route('blog.index') }}">
                            Back to blog
                        </flux:button>
                    </div>
                @endif
            </div>

        </div>
    </div>
    
    <livewire:account.livewire.mailing-list-signup-form/>
    
</div>
