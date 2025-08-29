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

            <form method="POST" action="{{ route('contact') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                    @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="inquiry" class="block text-sm font-medium text-gray-700">Inquiry</label>
                    <select id="inquiry" name="inquiry"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @foreach (\App\Core\Enums\InquiryType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('inquiry') === $type->value)>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('inquiry')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea id="message" name="message" rows="5" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('message') }}</textarea>
                    @error('message')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
                @error('g-recaptcha-response')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror

                <div>
                    <flux:button type="submit" variant="primary">Send</flux:button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</x-layouts.guest>
