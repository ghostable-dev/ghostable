<style>
  @keyframes ringPulse {
    0% {
      transform: scale(0.9);
      opacity: 0.6;
    }
    50% {
      transform: scale(1.4);
      opacity: 0.1;
    }
    100% {
      transform: scale(0.9);
      opacity: 0.6;
    }
  }

  .ring-anim {
    animation: ringPulse ease-in-out infinite;
    will-change: transform;
  }
</style>

<section class="relative py-48 w-full flex items-center justify-center bg-gradient-to-r from-brand-extra-dark to-black overflow-hidden">

  <!-- Animated Ring Cluster -->
  <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-0">
    <div class="relative w-[40vw] h-[40vw] max-w-[900px] max-h-[900px] opacity-50">

      <!-- Rings with staggered inset, speed, and opacity -->
      <div class="absolute inset-0 rounded-full border border-brand opacity-20 ring-anim" style="animation-duration: 6s;"></div>
      <div class="absolute inset-6 rounded-full border border-brand opacity-15 ring-anim" style="animation-duration: 7s;"></div>
      <div class="absolute inset-12 rounded-full border border-brand opacity-10 ring-anim" style="animation-duration: 8s;"></div>
      <div class="absolute inset-20 rounded-full border border-brand opacity-10 ring-anim" style="animation-duration: 9s;"></div>
      <div class="absolute inset-32 rounded-full border border-brand opacity-5 ring-anim" style="animation-duration: 10s;"></div>
      <div class="absolute inset-44 rounded-full border border-brand opacity-4 ring-anim" style="animation-duration: 12s;"></div>
      <div class="absolute inset-56 rounded-full border border-brand opacity-3 ring-anim" style="animation-duration: 14s;"></div>

    </div>
  </div>

  <!-- Foreground Content with Text Effects -->
  <div class="w-full text-center relative z-10">
    <flux:heading
      class="my-6 py-6 !font-bold leading-tighter tracking-tight !text-6xl lg:text-8xl bg-gradient-to-r from-brand to-brand-light bg-clip-text text-transparent"
      style="display: inline-block; vertical-align: top; text-decoration: inherit; max-width: 1000px;">
      AES-256-GCM Encryption
    </flux:heading>
    <flux:subheading size="xl" class="dark max-w-3xl mx-auto">
      AES-256-GCM encryption is enforced across storage, transport, and runtime,
      ensuring no unencrypted secrets exist at any stage.
    </flux:subheading>
  </div>

</section>