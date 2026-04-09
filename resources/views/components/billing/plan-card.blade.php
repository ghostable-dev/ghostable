@props([
  'name',
  'headingId' => null,
  'price' => null,
  'altPrice' => null,
  'featured' => false,
  'description' => '',
  'features' => [],
  'integrations' => [],
  'pl' => 'xl:pl-14',
  'pr' => 'xl:pr-14'
])
@php($resolvedHeadingId = $headingId ?: 'pricing-plan-'.str($name)->slug())

<div aria-labelledby="{{ $resolvedHeadingId }}" class="pt-16 lg:px-8 lg:pt-0 {{ $pl }} {{ $pr }}">
    <h3 id="{{ $resolvedHeadingId }}" class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ $name }}</h3>
    @isset($price)
        <p class="mt-2 flex items-baseline gap-x-1">
            <span class="text-5xl font-semibold tracking-tight text-gray-900 dark:text-white">${{ $price }}</span>
            <span class="text-sm/6 font-semibold text-gray-600 dark:text-gray-400">/month</span>
        </p>
    @endisset
    @isset($altPrice)
        <p class="mt-2 flex items-baseline gap-x-1">
            <span class="text-5xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $altPrice }}</span>
        </p>
    @endisset
    <div class="mt-6">
        @if($featured)
            <flux:button href="{{ route('login') }}" class="w-full" variant="primary">
                Most Popular
            </flux:button>
        @else
            <flux:button href="{{ route('login') }}" class="w-full" variant="filled">
                Get Started
            </flux:button>
        @endif
    </div>
    @isset($description)
        <p class="mt-10 text-sm/6 font-semibold text-gray-900 dark:text-white">
            {{ $description }}
        </p>
    @endisset
    <ul role="list" class="mt-6 space-y-3 text-sm/6 text-gray-600 dark:text-gray-300">
        @foreach($features as $feature)
            <li class="flex gap-x-3">
                <flux:icon.check-circle variant="micro"/>
                {{ $feature }}
            </li>
        @endforeach
    </ul>
    @if(count($integrations))
        <div class="mt-8 pt-2">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Integrations</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($integrations as $integration)
                    <span
                        class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold"
                        style="border-color: {{ $integration['accent'] ?? '#AC55FF' }}; background-color: {{ ($integration['fill'] ?? '#240642') }}; color: {{ $integration['text'] ?? '#F8F4F3' }};">
                        <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $integration['accent'] ?? '#AC55FF' }};"></span>
                        {{ $integration['label'] ?? 'Vanta' }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
