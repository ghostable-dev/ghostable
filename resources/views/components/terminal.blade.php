<div class="rounded-2xl shadow-lg ring-1 ring-black/5 my-10">
  <div class="grid grid-cols-1 rounded-2xl p-2 bg-white shadow-md">
    <div class="rounded-xl bg-white shadow-xl ring-1 ring-black/5">
<div
  @class([
    'relative overflow-hidden rounded-xl ',
    'bg-zinc-900 text-zinc-100'
  ])
  aria-label="Terminal Output Example">
  
    {{-- Terminal Top Bar --}}
    <div 
      @class([
        'flex items-center gap-2 px-4 py-3',
        'border-b border-zinc-800',
        'bg-black'
      ])>
        <span class="h-3 w-3 rounded-full bg-white/30"></span>
        <span class="h-3 w-3 rounded-full bg-white/50"></span>
        <span class="h-3 w-3 rounded-full bg-white/60"></span>
    </div>

    {{-- Terminal Content --}}
    <div class="relative px-6 py-6 font-mono text-xs md:text-base leading-relaxed space-y-3 overflow-hidden">
        {{ $slot }}
    </div>
</div>

</div></div></div>