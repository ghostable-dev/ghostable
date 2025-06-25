<section class="w-full bg-zinc-50 dark:bg-transparent py-24">
  <div class="mx-auto max-w-4xl">

    {{-- Section Heading --}}
    <div class="text-center px-10">
      <flux:heading level="2" class="!text-3xl md:!text-5xl !font-bold tracking-tight text-balance">
        Effortless, Secure Sharing
      </flux:heading>
      <flux:subheading size="xl">
        Keep your environment secrets out of inboxes and DMs—where they don’t belong.
      </flux:subheading>
    </div>

    {{-- Terminal Block --}}
    <x-terminal>
        <p><span class="text-zinc-500">></span> <span class="text-brand">ghostable</span> env:push</p>
        <div class="relative border border-zinc-700 rounded px-4 pt-6 pb-4 mt-4">
            <div class="absolute -top-3 left-4 px-1 bg-zinc-900 text-zinc-400 text-sm">
                Which environment would you like to push?
            </div>
            <div class="space-y-1 pt-1">
                <div class="text-zinc-400">○ Local</div>
                <div class="flex items-center gap-2 text-white">
                    <span class="text-brand">›</span>
                    <span class="text-brand">●</span>
                    <span>Staging</span>
                </div>
                <div class="text-zinc-400">○ Production</div>
            </div>
        </div>
        <p class="flex items-center gap-2">
            <flux:icon.check-circle class="h-4 w-4 text-brand" /> Environment 
            <span class="text-brand">Staging</span> pushed to Ghostable.
        </p>
        <p><span class="text-zinc-500">></span> (3) added, (5) updated, & (1) removed</p>
    </x-terminal>

    {{-- Feature Columns --}}
    <div class="grid grid-cols-1 gap-10 sm:grid-cols-3 mt-16 px-10">
      {{-- Column 1 --}}
      <div class="flex flex-col items-start space-y-4">
        <flux:icon.lock-closed class="text-brand" variant="solid"/>
        <flux:heading class="font-semibold" level="3" size="lg">
          Built-In Security
        </flux:heading>
        <flux:subheading>
          All environment files are encrypted in transit and at rest—keeping sensitive configs safe by default.
        </flux:subheading>
      </div>

      {{-- Column 2 --}}
      <div class="flex flex-col items-start space-y-4">
        <flux:icon.users class="text-brand" variant="solid"/>
        <flux:heading class="font-semibold" level="3" size="lg">
          Share with Precision
        </flux:heading>
        <flux:subheading>
          Grant access to teammates with fine-grained roles—no more blind sharing or permission confusion.
        </flux:subheading>
      </div>

      {{-- Column 3 --}}
      <div class="flex flex-col items-start space-y-4">
        <flux:icon.clock class="text-brand" variant="solid"/>
        <flux:heading class="font-semibold" level="3" size="lg">
          Full Visibility
        </flux:heading>
        <flux:subheading>
          Track every edit, push, and pull across your environments—right when it happens.
        </flux:subheading>
      </div>
    </div>

  </div>
</section>