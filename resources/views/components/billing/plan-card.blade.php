@props([
  'name',
  'price' => null,
  'altPrice' => null,
  'featured' => false,
  'description' => '',
  'features' => [],
  'pl' => 'xl:pl-14',
  'pr' => 'xl:pr-14'
])
{{-- class="xl:pl-14 xl:pr-14" --}}
<div class="pt-16 lg:px-8 lg:pt-0 {{ $pl }} {{ $pl }}">
    <h3 id="tier-basic" class="text-base/7 font-semibold text-gray-900 dark:text-white">{{ $name }}</h3>
    @if($price)
        <p class="mt-2 flex items-baseline gap-x-1">
            <span class="text-5xl font-semibold tracking-tight text-gray-900 dark:text-white">${{ $price }}</span>
            <span class="text-sm/6 font-semibold text-gray-600 dark:text-gray-400">/month</span>
        </p>
    @endif
    @if($altPrice)
        <p class="mt-2 flex items-baseline gap-x-1">
            <span class="text-5xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $altPrice }}</span>
        </p>
    @endif
    <div class="mt-6">
        @if($featured)
            <flux:button disabled class="w-full" variant="primary">
                {{-- Most Popular --}}
                Coming Soon
            </flux:button>
        @else
            <flux:button disabled class="w-full" variant="filled">
                {{-- Get Started --}}
                Coming Soon
            </flux:button>
        @endif
    </div>
    @if($description)
        <p class="mt-10 text-sm/6 font-semibold text-gray-900 dark:text-white">
            {{ $description }}
        </p>
    @endif
    <ul role="list" class="mt-6 space-y-3 text-sm/6 text-gray-600 dark:text-gray-300">
        @foreach($features as $feature)
            <li class="flex gap-x-3">
                <flux:icon.check-circle variant="micro"/>
                {{ $feature }}
            </li>
        @endforeach
    </ul>
</div>