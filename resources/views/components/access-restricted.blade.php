@props([
    'title' => __('Access Restricted'),
])

<div class="flex flex-col items-center justify-center gap-4 py-12 text-center">
    <x-icon.lock-closed class="w-10 h-10 text-red-500" />
    <flux:heading size="lg">{{ $title }}</flux:heading>
    <flux:text class="max-w-md">
        You don’t have permission to view this section.
        Please contact a team admin if you believe this is a mistake.
    </flux:text>
</div>