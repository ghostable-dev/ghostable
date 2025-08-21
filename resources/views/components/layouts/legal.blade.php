@props([
    'title' => null,
    'lastUpdated' => null    
])
<x-layouts.guest>
    <div class="py-12 px-4 space-y-8">
        <div class="mx-auto max-w-2xl text-left">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900">
                {{ $title }}
            </h1>
        </div>
        <div class="mx-auto max-w-2xl space-y-4">
            <p class="font-bold text-xs">
                @if($lastUpdated)
                    Last Updated:
                    <time datetime="{{ $lastUpdated->timezone(timezone())->toDateString() }}">
                        {{ $lastUpdated->timezone(timezone())->format('F d, Y') }}
                    </time>
                @endif
            </p>
            <div class="prose prose-zinc prose-sm">
                {!! $document !!}
            </div>
            <div class="pt-8">
                <div class="prose prose-zinc prose-sm">
                    <h2>Contact Us.</h2>
                    <p>If you have any questions regarding this document, or if you need to contact Ghostable for any reason, please do not hesitate to reach us at <a href="mailto:{{ config('contact.support.email') }}">{{ config('contact.support.email') }}</a>.</p>
                </div>
                <div class="mt-6">
                    <span class="text-sm font-bold">Published by:</span>
                    <address class="text-gray-500 text-xs">
                        GHOSTABLE LLC<br>
                        {{ config('contact.address.line1') }}<br>
                        {{ config('contact.address.line2') }}<br>
                        {{ config('contact.address.addressLocality') }} 
                        {{ config('contact.address.addressRegion') }} 
                        {{ config('contact.address.postalCode') }}<br>
                        {{-- {{ config('contact.phone') }} --}}
                    </address>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>