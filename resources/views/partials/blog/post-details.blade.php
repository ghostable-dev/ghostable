<div class="text-sm flex gap-2 items-center flex-wrap md:flex-nowrap">
      <p class="font-semibold leading-7 text-primary">
            {{ $post->category->label() }}
      </p>
      <span class="text-gray-400">|</span>
      <time datetime="{{ $post->posted_at->format('Y-m-d') }}">
            {{ $post->posted_at->isoFormat('ll') }}
      </time>
      <span class="text-gray-400">•</span>
      <span class="text-gray-400">
            {{ $post->readTime }} min read
      </span>
</div>