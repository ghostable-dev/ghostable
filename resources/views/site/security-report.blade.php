@push('meta')
    <x-seo-meta
        title="Report a Security Issue"
        description="Report a security vulnerability to Ghostable. Use the form or email security@ghostable.dev."
        :keywords="[
            'ghostable security',
            'report a security issue',
            'vulnerability disclosure',
            'security report'
        ]"/>
@endpush

@pushIf($recaptchaEnabled, 'scripts')
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaKey }}"></script>
@endPushIf

<x-layouts.guest title="Report a Security Issue" canonical="{{ route('security.report') }}">
    <div class="px-6 lg:px-8 py-16 bg-white">
        <div class="mx-auto lg:max-w-3xl space-y-10">
            <div>
                <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                    Report a security issue
                </h1>
                <p class="mt-6 max-w-2xl text-2xl font-medium text-gray-500">
                    Found a vulnerability or security concern? Share the details below and our team will investigate.
                    We take security seriously and review reports as quickly as possible.
                    Prefer email? Contact us at
                    <a class="text-gray-900 underline" href="mailto:{{ $securityEmail }}">{{ $securityEmail }}</a>.
                </p>
            </div>

            @if (session('status'))
                <div class="rounded-md bg-green-100 p-4">
                    <p class="text-sm text-green-700">{{ session('status') }}</p>
                </div>
            @endif

            <form
                class="space-y-6"
                method="POST"
                action="{{ route('security.report') }}"
                x-data="{recaptchaEnabled: @json($recaptchaEnabled)}"
                x-on:submit.prevent="
                    if(!this.recaptchaEnabled) {
                        $el.submit();
                    }
                    grecaptcha.ready(() => {
                        grecaptcha.execute('{{ $recaptchaKey }}', { action: 'security-report' }).then((token) => {
                            $refs.recaptcha_token.value = token;
                            $el.submit();
                        });
                    })">
                @csrf
                @if($recaptchaEnabled)
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token" x-ref="recaptcha_token">
                @endif
                <flux:input label="Name" id="name" name="name" value="{{ old('name') }}" required/>
                <flux:input label="Email" type="email" id="email" name="email" value="{{ old('email') }}" required/>
                <flux:textarea label="Issue details" id="message" name="message" rows="6" required>
                    {{ old('message') }}
                </flux:textarea>
                <div>
                    <flux:button type="submit" variant="primary">Submit report</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.guest>
