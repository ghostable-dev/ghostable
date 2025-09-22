@extends('mail.layouts.simple')

@php
    // single source of truth for the variant
    $reminder = (bool) ($reminder ?? false);

    // dynamic strings
    $title = $reminder
        ? 'Finish setting up your organization'
        : 'Create your Ghostable organization';

    $preheader = $reminder
        ? "You haven’t created your Ghostable organization yet — it only takes a minute to get started."
        : "Hey {$name}, let’s set up your Ghostable organization so you can start collaborating.";

    // CTA label
    $cta = $reminder ? 'Finish setup' : 'Create an organization';

    // Always generate absolute URLs in email
    $dashboardUrl = url()->route('dashboard', [], true);

    // Optional: UTM params for tracking (remove if you don’t want them)
    $dashboardUrl = \Illuminate\Support\Str::of($dashboardUrl)
        ->append('?utm_source=email&utm_medium=drip&utm_campaign=org-setup')
        ->when($reminder, fn($s) => $s->append('&variant=reminder'), fn($s) => $s->append('&variant=first'));
@endphp

@section('title', $title)

@section('preheader')
    {{ $preheader }}
@endsection

@section('content')

    <x-mail.simple.title size="lg">
        {{ $title }}
    </x-mail.simple.title>

    <x-mail.simple.paragraph>
        @if($reminder)
            You started with Ghostable but haven’t created your org yet. Your org is where team access,
            permissions, and shared secrets live. It takes about a minute.
        @else
            You’re one step away from a secure home for your team’s secrets. Create your org to unlock
            access controls and easy collaboration.
        @endif
    </x-mail.simple.paragraph>

    <x-mail.simple.button :href="$dashboardUrl">
        {{ $cta }}
    </x-mail.simple.button>

    <x-mail.simple.paragraph>
        @if($reminder)
            Need help? Check the <a href="https://docs.ghostable.dev">docs</a> to get started.
        @else
            Not sure what to name it? Most teams start with their company or project name. You can always update
            it later—what matters is getting a secure home for your secrets in place today.
        @endif
    </x-mail.simple.paragraph>

@endsection