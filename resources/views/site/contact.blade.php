@push('meta')
    <x-seo-meta
        title="Contact Ghostable"
        description="Have questions about Ghostable? Get in touch with our team to talk about pricing, enterprise options, or general inquiries."
        :keywords="[
            'ghostable contact',
            'contact ghostable',
            'support',
            'sales',
            'enterprise',
            'environment variables',
            'secrets management'
        ]"/>
@endpush
    
<x-layouts.guest title="Contact" canonical="{{ route('contact') }}">
    <div class="px-6 lg:px-8 py-16 bg-white">
        <div class="mx-auto lg:max-w-3xl space-y-10">
            <div>
                <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                    Get in touch
                </h1>
                <p class="mt-6 max-w-2xl text-2xl font-medium text-gray-500">
                    Need to ask a question or get account support? Contact us and we’ll get back to you as soon as we can!
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
                action="{{ route('contact') }}" 
                x-data="{recaptchaEnabled: @json($recaptchaEnabled)}"
                x-on:submit.prevent="
                    if(!this.recaptchaEnabled) {
                        $el.submit();
                    }
                    grecaptcha.ready(() => {
                        grecaptcha.execute('{{ $recaptchaKey }}', { action: 'contact' }).then((token) => {
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
                <flux:select label="How can we help?" id="inquiry" name="inquiry">
                    @foreach (\App\Core\Enums\InquiryType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('inquiry') === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </flux:select>
                <flux:textarea label="Message" id="message" name="message" rows="5" required>
                    {{ old('message') }}
                </flux:textarea>
                <div>
                    <flux:button type="submit" variant="primary">Submit</flux:button>
                </div>
            </form>
            
        </div>
    </div>
</x-layouts.guest>

@pushIf($recaptchaEnabled, 'scripts')
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaKey }}"></script>
@endPushIf