<div 
    x-data="{
        tab: 'sharing',
    }"
    class="text-center">
    @php
        $selectedClasses = "border border-2 !border-blue-600 shadow-[0_0_0_1px_#ffffff11,0_0_24px_#00000033]";
    @endphp
    <flux:heading level="2" size="xl">
        Everything you need to manage .env files—with confidence.
    </flux:heading>
    <flux:subheading size="xl">
        From secure sharing to real-time validation, Ghostable gives your Laravel team superpowers.
    </flux:subheading>
    <div class="mx-auto mt-10 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 lg:mx-0 lg:max-w-none lg:grid-cols-3">
        <flux:card 
            class="flex max-w-xl flex-col items-start text-left space-y-4"
            
            x-bind:class="{'{{ $selectedClasses }}': tab === 'sharing'}"
            x-on:click="tab = 'sharing'">
            <flux:icon.lock-closed variant="solid"/>
            <flux:heading level="3">Secure .env Sharing</flux:heading>
            <flux:subheading>Easily and safely share environment files across teams without email, Slack, or Notion.</flux:subheading>
        </flux:card>
        <flux:card 
            class="flex max-w-xl flex-col items-start text-left space-y-4"
            x-bind:class="{'{{ $selectedClasses }}': tab === 'validation'}"
            x-on:click="tab = 'validation'">
            <flux:icon.check-badge variant="solid"/>
            <flux:heading level="3">Enforced Validation</flux:heading>
            <flux:subheading>Define rules for required keys, types, and values—Ghostable flags issues before they break your app.</flux:subheading>
        </flux:card>
        <flux:card 
            class="flex max-w-xl flex-col items-start text-left space-y-4"
            x-bind:class="{'{{ $selectedClasses }}': tab === 'history'}"
            x-on:click="tab = 'history'">
            <flux:icon.eye variant="solid"/>
            <flux:heading level="3">Full History & Visibility</flux:heading>
            <flux:subheading>Track every change to every key. See what changed, when, and by whom. Roll back instantly.</flux:subheading>
        </flux:card>
    </div>
    
    <div class="max-w-4xl mx-auto py-12">
        <div x-show="tab === 'sharing'" class="text-center">
            <div class="w-full">
                <div class="w-[700px] mx-auto">
                    <div class="relative box-content h-full overflow-hidden px-12 pt-12" style="
                        --mask-right: linear-gradient(to right, #000 50%, transparent 100%);
                        --mask-bottom: linear-gradient(to bottom, #000 80%, transparent 100%);
                        mask-image: var(--mask-bottom), var(--mask-right);
                        mask-composite: intersect;">
                        <div class="bg-zinc-100 h-full space-y-4 rounded-lg p-6 shadow-[0_0_0_1px_#00000022,0_0_24px_#00000011]">
                            Sharing
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div x-show="tab === 'validation'">
            <div class="max-w-4xl mx-auto py-12">
                <div class="relative -mx-6 -mt-6 box-content h-full overflow-hidden px-6 pt-6" style="
                    --mask-right: linear-gradient(to right, #000 50%, transparent 100%);
                    --mask-bottom: linear-gradient(to bottom, #000 80%, transparent 100%);
                    mask-image: var(--mask-bottom), var(--mask-right);
                    mask-composite: intersect;">
                    <div class="bg-default h-full w-[800px] space-y-4 rounded-lg p-6 shadow-[0_0_0_1px_#00000022,0_0_24px_#00000011]">
                        Validation
                    </div>
                </div>
            </div>
        </div>
        <div x-show="tab === 'history'">
            <div class="max-w-4xl mx-auto py-12">
                <div class="max-w-4xl mx-auto py-12">
  <div class="relative -mx-8 -mt-8 box-content h-full overflow-hidden px-8 pt-24"
    style="
      --mask-bottom: linear-gradient(to bottom, #000 60%, transparent 100%);
      --mask-left: linear-gradient(to left, #000 70%, transparent 100%);
      mask-image: var(--mask-bottom), var(--mask-left);
      mask-composite: intersect;
      -webkit-mask-image: var(--mask-bottom), var(--mask-left);
      -webkit-mask-composite: destination-in;
    "
  >
    <div class="relative origin-top-right scale-100 sm:scale-100">
      <div class="bg-default text-weak w-[700px] space-y-2 rounded-lg p-4 font-mono text-[13px] shadow-[0_0_0_1px_#00000022,0_0_24px_#00000011]">

        <!-- Header -->
        <div class="mb-4 text-left text-strong text-base font-medium">
          Enforced Validation
          <div class="text-weak text-sm font-normal">
            Define rules for required keys, types, and values—Ghostable flags issues before they break your app.
          </div>
        </div>

        <!-- Valid entry -->
        <div class="flex gap-6 rounded-md border border-transparent p-1.5 hover:bg-weak">
          <p class="shrink-0 text-strong text-xs">APP_ENV</p>
          <p class="text-white">production</p>
        </div>

        <!-- Warning: unexpected value -->
        <div class="flex gap-6 rounded-md border border-yellow-500/20 bg-yellow-500/5 p-1.5 text-yellow-300">
          <p class="shrink-0 text-yellow-400 text-xs">APP_DEBUG</p>
          <p>true <span class="ml-2 italic text-yellow-300">Expected: false in production</span></p>
        </div>

        <!-- Error: required key missing -->
        <div class="flex gap-6 rounded-md border border-[#ff003f]/20 bg-[#ff003f]/10 p-1.5 text-[#ff003f]">
          <p class="shrink-0 text-xs">APP_KEY</p>
          <p class="italic">Missing required key</p>
        </div>

        <!-- Info: valid with pattern -->
        <div class="flex gap-6 rounded-md border border-transparent p-1.5 hover:bg-weak">
          <p class="shrink-0 text-strong text-xs">APP_URL</p>
          <p class="text-white">https://app.ghostable.dev</p>
        </div>

        <!-- Notice: invalid enum value -->
        <div class="flex gap-6 rounded-md border border-orange-500/20 bg-orange-500/5 p-1.5 text-orange-300">
          <p class="shrink-0 text-xs">QUEUE_DRIVER</p>
          <p>legacy <span class="ml-2 italic">Invalid: must be one of [database, redis]</span></p>
        </div>

      </div>
    </div>
  </div>
</div>
            </div>
        </div>
    </div>

</div>