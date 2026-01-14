<footer>
    <div class="mx-auto w-full [:where(&)]:max-w-7xl px-6 lg:px-8 p-6 py-12 lg:p-8" >

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-center sm:text-left gap-6 sm:gap-4 dark">

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
                <button
                    x-data="ghostableStatus()"
                    x-init="init()"
                    @click="window.open('https://ghostable.statuspage.io', '_blank')"
                    type="button"
                    class="inline-flex items-center gap-2 px-1 py-1 text-xs font-medium text-white hover:text-white transition"
                    title="View full status page"
                >
                    <span class="inline-flex h-2.5 w-2.5 rounded-full" :class="dotClass"></span>
                    <span x-text="statusText"></span>
                </button>
                
            </div>

            <!-- Right: Links -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                <div class="flex flex-wrap justify-center sm:justify-start gap-x-4 gap-y-2 text-sm">
                    <flux:link href="{{ route('terms')}}" variant="subtle">Terms</flux:link>
                    <flux:link href="{{ route('privacy')}}" variant="subtle">Privacy</flux:link>
                    <flux:link href="{{ route('security.report') }}" variant="subtle">Security</flux:link>
                    <flux:link href="{{ route('contact')}}" variant="subtle">Contact</flux:link>
                </div>
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('ghostableStatus', () => ({
                statusText: 'Status...',
                indicator: 'loading',
                apiUrl: 'https://ghostable.statuspage.io/api/v2/status.json',
                async init() {
                    try {
                        const response = await fetch(this.apiUrl, { cache: 'no-store' });
                        if (!response.ok) throw new Error('Bad response');
                        const data = await response.json();
                        this.indicator = data.status?.indicator || 'none';
                        this.statusText = data.status?.description || 'Operational';
                    } catch (error) {
                        this.indicator = 'error';
                        this.statusText = 'Status unavailable';
                    }
                },
                get dotClass() {
                    switch (this.indicator) {
                        case 'none':
                            return 'bg-emerald-400';
                        case 'minor':
                            return 'bg-amber-400';
                        case 'major':
                        case 'critical':
                            return 'bg-rose-500';
                        default:
                            return 'bg-slate-300';
                    }
                },
            }));
        });
    </script>
</footer>
