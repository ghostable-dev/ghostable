<x-layouts.auth title="Ghostable CLI Login">
    @php
        $messages = [
            'approved' => [
                'title' => 'Login Approved',
                'text' => 'Your CLI session has been approved. Return to the Ghostable CLI to finish signing in.',
            ],
            'already-approved' => [
                'title' => 'Already Approved',
                'text' => 'This CLI session has already been approved. You can close this window.',
            ],
            'expired' => [
                'title' => 'Session Expired',
                'text' => 'This CLI login session has expired. Please start a new login from the Ghostable CLI.',
            ],
        ];

        $state = $state ?? 'expired';
        $msg = $messages[$state] ?? $messages['expired'];
    @endphp
    
    @push('meta')
        <x-seo-meta
            :title="$msg['title']"
            :description="$msg['text']"
            :keywords="[]"
            robots="noindex, nofollow, noarchive, noimageindex"
        />
    @endpush

    <div class="flex flex-col gap-3 text-center">
        <flux:heading class="!text-5xl font-medium tracking-tighter text-pretty text-balance">
            {{ $msg['title'] }}
        </flux:heading>

        <flux:subheading>
            {{ $msg['text'] }}
        </flux:subheading>

        <p class="mx-auto py-2">
            <flux:button href="{{ url('/') }}">Return to Ghostable</flux:button>
        </p>
    </div>
</x-layouts.auth>