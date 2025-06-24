<section class="w-full bg-white dark:bg-zinc-950 py-24 border-t border-zinc-100 dark:border-zinc-800">
  <div class="mx-auto max-w-4xl">

    {{-- Section Heading --}}
    <div class="text-center">
      <flux:heading level="2" class="!text-5xl !font-bold tracking-tight text-balance">
        Enforced Validation, No Surprises
      </flux:heading>
      <flux:subheading size="xl">
        Automatically validate environment variables before deploys — so you never push broken config again.
      </flux:subheading>
    </div>

    {{-- Validation Preview --}}
    <x-terminal>
        <p>
            <span class="text-zinc-500">></span> Validating <span class="text-brand">Production</span>...
        </p>
        <p class="flex items-center gap-2">
            <flux:icon.check-circle class="h-4 w-4 text-green-400" /> APP_KEY is present
        </p>
        <p class="flex items-center gap-2">
            <flux:icon.check-circle class="h-4 w-4 text-green-400" />DB_CONNECTION is one of: mysql
        </p>
        <p class="flex items-center gap-2">
            <flux:icon.exclamation-circle class="h-4 w-4 text-yellow-400" />QUEUE_CONNECTION is set to sync (recommended: redis)
        </p>
        <p class="flex items-center gap-2">
            <flux:icon.x-circle class="h-4 w-4 text-red-500" />APP_DEBUG must be false in production
        </p>
    </x-terminal>


    {{-- Feature Highlights --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-10 mt-16 text-zinc-900 dark:text-zinc-100">
      {{-- Rule Builder --}}
      <div class="flex flex-col space-y-4">
        <flux:icon.check-badge class="text-brand" />
        <flux:heading class="font-semibold" level="3">Custom Rules</flux:heading>
        <flux:subheading>
          Define required keys, types, lengths, and allowed values for each project.
        </flux:subheading>
      </div>

      {{-- CI/CD Ready --}}
      <div class="flex flex-col space-y-4">
        <flux:icon.rocket-launch class="text-brand" />
        <flux:heading class="font-semibold" level="3">Preflight Validation</flux:heading>
        <flux:subheading>
          Validate your .env before merging or deploying—automatically.
        </flux:subheading>
      </div>

      {{-- Team Enforcement --}}
      <div class="flex flex-col space-y-4">
        <flux:icon.shield-exclamation class="text-brand" />
        <flux:heading class="font-semibold" level="3">Team Enforcement</flux:heading>
        <flux:subheading>
          Enforce consistent config rules across teams, environments, and pipelines.
        </flux:subheading>
      </div>
    </div>

  </div>
</section>