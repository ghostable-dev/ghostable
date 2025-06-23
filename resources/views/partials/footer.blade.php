<footer class="[grid-area:footer]" container="container" data-flux-footer="">
  <div class="mx-auto w-full [:where(&)]:max-w-7xl px-6 lg:px-8 p-6 lg:p-8" data-flux-container="">
    <flux:separator variant="subtle"/>
    <div class="mt-16 py-12 flex max-xl:flex-col max-xl:gap-4 items-center justify-between">

      <!-- Left: Copyright and Links -->
      <flux:subheading>
        &copy; 2025 Ghostable LLC
        <span class="max-sm:hidden mx-1">·</span>
        <flux:link 
            href="{{ route('terms')}}"
            variant="subtle">
            Terms
        </flux:link>
        <span class="max-sm:hidden mx-1">·</span>
        <flux:link 
            href="{{ route('privacy')}}"
            variant="subtle">
            Privacy
        </flux:link>
        <span class="max-sm:hidden mx-1">·</span>
        <flux:link 
            href="https://docs.ghostable.dev"
            variant="subtle">
            Documentation
        </flux:link>
      </flux:heading>

      <!-- Right: Credits -->
      <flux:subheading class="text-center">
        Built with
        <flux:icon.heart variant="micro" class="inline-flex"/>
        by
        <flux:link variant="ghost" href="{{ url('') }}">
            the Ghostable team
        </flux:link>
      </flux:subheading>

    </div>
  </div>
</footer>