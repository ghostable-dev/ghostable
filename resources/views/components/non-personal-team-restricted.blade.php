@props([
    'title' => __('Available to Organization Workspaces'),
])

<div class="flex flex-col items-center justify-center gap-4 py-12 text-center">
    <x-icon.users class="w-10 h-10 text-blue-500" />
    <flux:heading size="lg">{{ $title }}</flux:heading>
    <flux:text class="max-w-md">
        This feature is only available to non-personal organizations.
        Upgrade to a shared workspace to unlock access.
    </flux:text>
</div>