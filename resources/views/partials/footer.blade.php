<footer>
    <div class="mx-auto w-full [:where(&)]:max-w-7xl px-6 lg:px-8 p-6 py-12 lg:p-8" >

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-center sm:text-left gap-2 sm:gap-4 dark">

            <!-- Left: Copyright and Links -->
            <div class="flex flex-col items-center sm:items-start gap-4">
                <div class="flex flex-wrap justify-center sm:justify-start gap-x-4 gap-y-2 text-sm">
                    <flux:link 
                        href="{{ config('contact.social.github') }}" 
                        variant="subtle" 
                        target="_blank">
                        <flux:icon.github variant="mini"/>
                    </flux:link>
                    <flux:link 
                        href="{{ config('contact.social.discord') }}" 
                        variant="subtle" 
                        target="_blank">
                        <flux:icon.discord variant="mini"/>
                    </flux:link>
                </div>
                <flux:subheading>&copy; {{ date('Y') }} Ghostable, LLC</flux:subheading>
                
            </div>

            <!-- Right: Links -->
            <div class="flex flex-wrap justify-center sm:justify-start gap-x-4 gap-y-2 text-sm">
                <flux:link href="{{ route('terms')}}" variant="subtle">Terms</flux:link>
                <flux:link href="{{ route('privacy')}}" variant="subtle">Privacy</flux:link>
                <flux:link href="{{ route('contact')}}" variant="subtle">Contact</flux:link>
            </div>

        </div>
    </div>
</footer>
