@props([
    'title' => __('Paid Plan Required'),
])

<div class="flex flex-col items-center justify-center gap-4 py-12 text-center">
    <x-icon.credit-card class="w-10 h-10 text-gray-400" />
    <flux:heading size="lg">{{ $title }}</flux:heading>
    <flux:text class="max-w-md">
        This feature is only available on paid plans.
    </flux:text>
</div>
