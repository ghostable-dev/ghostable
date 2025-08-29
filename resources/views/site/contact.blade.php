<x-layouts.guest title="Ghostable - Contact">
    @include('partials.site-header')

    <div class="px-6 lg:px-8 py-16 bg-white">
        <div class="mx-auto lg:max-w-3xl space-y-10">
            <div>
                <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                    Get in touch
                </h1>
                <p class="mt-6 max-w-2xl text-2xl font-medium text-gray-500">
                    We'd love to hear from you. Select an inquiry type and send us a message.
                </p>
            </div>

            @if (session('status'))
                <div class="rounded-md bg-green-100 p-4">
                    <p class="text-sm text-green-700">{{ session('status') }}</p>
                </div>
            @endif

            <form 
                method="POST" 
                action="{{ route('contact') }}" 
                x-data 
                x-on:submit.prevent="grecaptcha.ready(() => {
                    grecaptcha.execute('{{ config('services.recaptcha.key') }}', { action: 'contact' }).then((token) => {
                        $refs.recaptcha_token.value = token;
                        $el.submit();
                    });
                })"
                class="space-y-6">
                @csrf
                <input type="hidden" name="recaptcha_token" id="recaptcha_token" x-ref="recaptcha_token">
                <flux:input label="Name" id="name" name="name" value="{{ old('name') }}" required/>
                <flux:input label="Email" type="email" id="email" name="email" value="{{ old('email') }}" required/>
                <flux:select label="Inquiry" id="inquiry" name="inquiry">
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
                    <flux:button type="submit" variant="primary">Send</flux:button>
                </div>
            </form>
            
        </div>
    </div>

    @push('scripts')
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.key') }}"></script>
    @endpush
</x-layouts.guest>
