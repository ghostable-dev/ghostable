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
                        href="{{ config('contact.social.x') }}" 
                        variant="subtle" 
                        target="_blank">
                        <flux:icon.x variant="mini"/>
                    </flux:link>
                    <flux:link 
                        href="{{ config('contact.social.youtube') }}" 
                        variant="subtle" 
                        target="_blank">
                        <flux:icon.youtube variant="mini"/>
                    </flux:link>
                </div>
                <flux:subheading>&copy; {{ date('Y') }} Ghostable, LLC</flux:subheading>
                <div class="inline-flex items-center gap-3">
                    <flux:link href="{{ route('trust') }}" variant="subtle" class="inline-flex">
                        <flux:badge variant="soft" size="sm" color="slate">SOC 2 Aligned</flux:badge>
                    </flux:link>
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
                
            </div>

            <!-- Right: Links -->
            <div class="flex flex-col items-center gap-3 sm:items-end">
                <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm sm:justify-end">
                    <span class="font-medium text-white/50">Licensing</span>
                    <flux:link href="{{ route('licenses') }}" variant="subtle">Purchase</flux:link>
                    @php
                        $licenseManagementAccess = session('license_management_access');
                        $hasLicenseManagementAccess = is_array($licenseManagementAccess)
                            && is_string($licenseManagementAccess['email'] ?? null)
                            && is_int($licenseManagementAccess['expires_at'] ?? null)
                            && $licenseManagementAccess['expires_at'] > now()->getTimestamp();
                    @endphp
                    @if(auth()->check() || $hasLicenseManagementAccess)
                        <flux:link href="{{ route('licenses.manage') }}" variant="subtle">Manage licenses</flux:link>
                    @else
                        <flux:modal.trigger name="license-management">
                            <flux:link as="button" variant="subtle">Manage licenses</flux:link>
                        </flux:modal.trigger>
                    @endif
                </div>
                <div class="flex flex-wrap justify-center sm:justify-start gap-x-4 gap-y-2 text-sm">
                    <flux:link href="{{ route('terms')}}" variant="subtle">Terms</flux:link>
                    <flux:link href="{{ route('privacy')}}" variant="subtle">Privacy</flux:link>
                    <flux:link href="{{ route('security.report') }}" variant="subtle">Security</flux:link>
                    <flux:link href="{{ route('contact')}}" variant="subtle">Contact</flux:link>
                </div>
            </div>

        </div>
    </div>

    <flux:modal
        name="license-management"
        :show="$errors->licenseManagement->isNotEmpty() || session()->has('license_management_link_sent') || session()->has('license_management_required')"
        class="dark md:w-md">
        @if(session()->has('license_management_link_sent'))
            <div class="space-y-6">
                <div class="space-y-2">
                    <flux:heading size="lg">Check your email</flux:heading>
                    <flux:text>
                        If that address has an active Ghostable license, we've sent a secure management link. It may take a few minutes to arrive.
                    </flux:text>
                </div>
                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="primary">Done</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('licenses.manage.request') }}" class="space-y-6">
                @csrf

                <div class="space-y-2">
                    <flux:heading size="lg">Manage licenses</flux:heading>
                    <flux:text>
                        Enter the email address used for your purchase. We'll send a temporary link to view your active licenses.
                    </flux:text>
                </div>

                <flux:field>
                    <flux:label>Purchase email</flux:label>
                    <flux:input
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        :invalid="$errors->licenseManagement->has('email')"
                        required
                    />
                    <flux:error name="email" bag="licenseManagement" />
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Email management link</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

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
