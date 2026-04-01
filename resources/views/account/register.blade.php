@if(! $embedded)
    @push('meta')
        <x-seo-meta
            title="Create your Ghostable account"
            description="Sign up to securely manage and share environment variables, with validation, version history, and full audit visibility."
            :keywords="[]"
            robots="noindex, nofollow, noarchive, noimageindex"
        />
    @endpush
@endif

<div class="flex flex-col gap-6">
    @if($showHeading)
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />
    @endif

    @include('account.partials.register-form', [
        'submitLabel' => $submitLabel,
        'showLoginLink' => $showLoginLink,
        'showNameField' => $showNameField,
    ])
</div>
