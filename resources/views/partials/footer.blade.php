<footer 
    class="[grid-area:footer] dark" 
    container="container" 
    data-flux-footer="">
    <div 
        class="mx-auto w-full [:where(&)]:max-w-7xl px-6 lg:px-8 p-6 sm:py-8 lg:p-8" 
        data-flux-container="">
            <flux:separator variant="subtle"/>
        
            <div class="mt-12 sm:mt-16 flex flex-col sm:flex-row sm:items-center sm:justify-between text-center sm:text-left gap-8 sm:gap-4">

            <!-- Left: Copyright and Links -->
            <div class="flex flex-col items-center sm:items-start gap-4">
                <flux:subheading>&copy; {{ date('Y') }} Ghostable LLC</flux:subheading>
                <div class="flex flex-wrap justify-center sm:justify-start gap-x-4 gap-y-2 text-sm">
                    <flux:link href="{{ route('terms')}}" variant="subtle">Terms</flux:link>
                    <flux:link href="{{ route('privacy')}}" variant="subtle">Privacy</flux:link>
                    <flux:link href="https://docs.ghostable.dev" variant="subtle">Documentation</flux:link>
                </div>
            </div>

            <!-- Right: Credits -->
            <flux:subheading class="text-center sm:text-left">
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