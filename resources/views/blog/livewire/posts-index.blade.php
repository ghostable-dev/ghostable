@section('title', 'Blog | AI Jobs Research, Insights, and Expertise')

@push('meta')
<x-core.seo-meta
    title="Blog | AI Jobs Research, Insights, and Expertise"
    description="Discover the latest AI job trends, technologies, research, interviews, and career expertise. Perspective and insights from AI experts."
    :keywords="[]"/>
@endpush

<div class="bg-white lg:-mt-28">
    <div class="relative w-full text-center pointer-events-none mb-16">
        <span class="text-center relative leading-loose z-10 font-black text-transparent w-full text-8xl sm:text-[11rem] lg:text-[13rem] xl:text-[15rem] bg-clip-text opacity-30 bg-gradient-to-r from-primary-extra-light to-primary-dark whitespace-nowrap">
            <span>Blog</span>
            <div class="absolute left-0 -mx-4 bottom-0 -mb-8 z-10 blur-lg h-[60%] w-screen bg-gradient-to-b from-transparent via-[#ECECFC] to-[#ECECFC]"></div>
        </span>
        <h1 class="text-center relative z-20 font-bold text-white text-4xl md:text-5xl -mt-14 sm:-mt-22 lg:-mt-32 max-w-3xl mx-auto">
            <div class="inline-block relative z-20 font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-gray-800 to-gray-900 pb-3 -mb-3">AI Jobs Research & Insights</div>
        </h1>
        <p class="text-lg pt-2">Explore the latest AI job trends, technologies, and industry research.</p>
    </div>
    
    <div class="py-8 px-4">
        <div class="mx-auto max-w-2xl">
            <div class="space-y-12 lg:space-y-20">
                
                @foreach($posts as $post)
                    <article class="relative isolate flex flex-col gap-8 lg:flex-row">
                        <div class="relative aspect-[16/9] lg:w-52 lg:shrink-0">
                            @if($post->hero)
                            <img src="{{ route('s3.asset', $post->hero) }}" 
                              alt="{{ $post->title }}" 
                              class="absolute inset-0 h-full w-full rounded-2xl bg-gray-50 object-cover"/>
                            @endif
                            <div class="absolute inset-0 rounded-2xl ring-1 ring-inset ring-gray-900/10"></div>
                        </div>
                        <div class="group relative max-w-xl">
                            @include('partials.blog.post-details')
                            <h2 class="mt-1 text-xl font-bold leading-6 text-gray-900 group-hover:text-gray-600">
                              <a href="{{ route('blog.view-post', $post) }}">
                                <span class="absolute inset-0"></span>
                                {{ $post->title }}
                              </a>
                            </h2>
                            <p class="mt-5 text-md leading-6 text-gray-600">{{ $post->description }}</p>
                            <div class="mt-3">
                              <a href="{{ route('blog.view-post', $post) }}" class="text-md font-semibold text-primary hover:text-primary-light underline">Read full story</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            {{ $posts->links() }}
        </div>
    </div>
    
    <livewire:account.livewire.mailing-list-signup-form/>
</div>