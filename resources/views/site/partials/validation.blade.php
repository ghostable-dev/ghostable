<section class="w-full py-24 bg-zinc-50">
  <div class="mx-auto max-w-4xl">

    {{-- Section Heading --}}
    <div class="text-center px-10">
      <flux:heading level="2" class="!text-3xl md:!text-5xl !font-medium tracking-tighter text-pretty">
        Enforced Validation, No Surprises
      </flux:heading>
      <flux:subheading size="xl">
        Validate environment variables before deploys — so you never push broken config again.
      </flux:subheading>
    </div>

    {{-- Validation Preview --}}
    <x-terminal>
        <p>
            <span class="text-zinc-500">></span> Validating <span class="text-brand">Production</span>...
        </p>
        <p class="flex items-center gap-2">
            <flux:icon.check-circle class="h-4 w-4 text-brand" /> APP_KEY is present
        </p>
        <p class="flex items-center gap-2">
            <flux:icon.check-circle class="h-4 w-4 text-brand" />DB_CONNECTION is one of: mysql
        </p>
        <p class="flex items-center gap-2">
            <flux:icon.exclamation-circle class="h-4 w-4 text-yellow-500" />QUEUE_CONNECTION is set to sync (recommended: redis)
        </p>
        <p class="flex items-center gap-2">
            <flux:icon.x-circle class="h-4 w-4 text-red-500" />APP_DEBUG must be false in production
        </p>
    </x-terminal>


    {{-- Feature Highlights --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-10 mt-16 px-10">
      {{-- Rule Builder --}}
      <div class="flex flex-col space-y-4">
        <flux:icon.check-badge class="text-brand" variant="solid"/>
        <flux:heading class="font-semibold" level="3" size="lg">
          Custom Rules
        </flux:heading>
        <flux:subheading>
          Define required keys, types, lengths, and allowed values for each project.
        </flux:subheading>
      </div>

      {{-- CI/CD Ready --}}
      <div class="flex flex-col space-y-4">
        <flux:icon.rocket-launch class="text-brand" variant="solid"/>
        <flux:heading class="font-semibold" level="3" size="lg"> 
          Preflight Validation
        </flux:heading>
        <flux:subheading>
          Validate your .env before merging or deploying—automatically.
        </flux:subheading>
      </div>

      {{-- Organization Enforcement --}}
      <div class="flex flex-col space-y-4">
        <flux:icon.shield-exclamation class="text-brand" variant="solid"/>
        <flux:heading class="font-semibold" level="3" size="lg">
          Organization Enforcement
        </flux:heading>
        <flux:subheading>
          Enforce consistent config rules across organizations, environments, and pipelines.
        </flux:subheading>
      </div>
    </div>

  </div>
</section>