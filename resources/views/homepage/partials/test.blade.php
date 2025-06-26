<div x-data="encryptFlow()" x-init="start()" class="relative w-full overflow-hidden bg-black py-24 flex items-center justify-center text-white font-mono">
  <!-- Glowing vertical encryption line -->
  <div class="absolute left-1/2 top-0 h-full w-[3px] bg-teal-400 shadow-[0_0_30px_8px_#14b8a6] z-10"></div>

  <!-- Animated string container -->
  <div class="relative w-full max-w-6xl h-12 overflow-visible">
    <template x-for="item in strings" :key="item.id">
      <div 
        class="absolute top-0 transition-all duration-100 ease-linear whitespace-nowrap text-xl"
        :style="{
          transform: `translateX(${item.x}px)`,
          opacity: item.visible ? 1 : 0
        }"
        :class="item.encrypted ? 'text-green-400 blur-[1px] opacity-70' : 'text-zinc-200'"
        x-text="item.text"
      ></div>
    </template>
  </div>
</div>

<script>
  function encryptFlow() {
    const plain = [
      'APP_KEY', 'DB_PASSWORD', 'JWT_SECRET',
      'STRIPE_SECRET', 'MAIL_HOST', 'REDIS_URL'
    ];

    const encryptedMap = {
      'APP_KEY': '98e5f2...29df91',
      'DB_PASSWORD': '$2y$10$x...A8cH3!',
      'JWT_SECRET': 'eyJhbGciOiJIUzI1N...',
      'STRIPE_SECRET': 'sk_live_23a9kdf...',
      'MAIL_HOST': 'smtp.mailtrap.io',
      'REDIS_URL': 'rediss://u:p@r.cache.amazonaws.com'
    };

    let id = 0;

    return {
      strings: [],
      start() {
        // Add a new string every 1.5s
        this.queueNext();
        setInterval(() => this.queueNext(), 1500);
      },
      queueNext() {
        const key = plain[Math.floor(Math.random() * plain.length)];
        const encrypted = encryptedMap[key] || '****';

        const item = {
          id: id++,
          text: key,
          x: -400,
          encrypted: false,
          visible: true
        };

        this.strings.push(item);

        // Animate
        const interval = setInterval(() => {
          item.x += 4;

          // Transform when crossing center
          if (!item.encrypted && item.x >= window.innerWidth / 2 - 100) {
            item.text = encrypted;
            item.encrypted = true;
          }

          // Remove off screen
          if (item.x > window.innerWidth + 100) {
            item.visible = false;
            clearInterval(interval);
          }
        }, 16); // 60fps-ish
      }
    };
  }
</script>