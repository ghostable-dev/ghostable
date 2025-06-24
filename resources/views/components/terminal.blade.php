<div
  class="relative overflow-hidden rounded-xl shadow-xl my-10 mx-5 md:mx-auto max-w-3xl bg-zinc-900 text-zinc-100 dark:bg-white dark:text-zinc-900"
  aria-label="Terminal Output Example">
  
    {{-- Terminal Top Bar --}}
    <div class="flex items-center gap-2 px-4 py-3 bg-black border-b border-zinc-800 dark:bg-yellow-500 dark:border-zinc-100">
        <span class="h-3 w-3 rounded-full bg-white/30"></span>
        <span class="h-3 w-3 rounded-full bg-white/50"></span>
        <span class="h-3 w-3 rounded-full bg-white/60"></span>
    </div>

    {{-- Terminal Content --}}
    <div class="relative px-6 py-6 font-mono text-base leading-relaxed space-y-3 overflow-hidden">
        {{ $slot }}
    </div>
</div>