<style>
  @keyframes ringPulse {
    0% {
      transform: scale(0.6);
      opacity: 0.6;
    }
    50% {
      transform: scale(1.8);
      opacity: 0.1;
    }
    100% {
      transform: scale(0.6);
      opacity: 0.6;
    }
  }

  .ring-anim {
    animation: ringPulse ease-in-out infinite;
    will-change: transform;
  }
</style>

<section 
    @class([
        'py-48',
        'relative w-full flex items-center justify-center overflow-hidden',
        'bg-gradient-to-r from-zinc-900 to-black'
    ])>
    
    {{-- Ring cluster --}}
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0">
        @php
            $ringCount = 300;
            $rings = collect(range(1, $ringCount))->map(function () {
                return [
                    'inset' => rand(1, 50),
                    'opacity' => rand(5, 20),
                    'duration' => rand(5, 40),
                ];
            });
        @endphp
        <div class="relative w-[65vw] h-[65vw] max-w-[900px] max-h-[900px] opacity-50">
            @foreach($rings as $ring)
                <div @class([
                    "absolute rounded-full border border-brand ring-anim",
                    "inset-{$ring['inset']}",
                    "opacity-{$ring['opacity']}"
                ]) 
                style="animation-duration: {{ $ring['duration'] }}s;"></div>
            @endforeach
        </div>
  </div>

  <!-- Foreground Content with Text Effects -->
  <div class="w-full text-center relative z-10">
    <flux:heading
      @class([
        'inline my-6 py-6 !font-bold leading-tighter tracking-tighter',
        '!text-7xl lg:text-8xl',
        'bg-gradient-to-r from-brand to-brand-light bg-clip-text text-transparent'
      ])>
      AES-256-GCM Encryption
    </flux:heading>
    <flux:subheading size="xl" class="dark max-w-3xl mx-auto py-6">
      AES-256-GCM encryption is enforced across storage, transport, and runtime,
      ensuring no unencrypted secrets exist at any stage.
    </flux:subheading>
  </div>

</section>