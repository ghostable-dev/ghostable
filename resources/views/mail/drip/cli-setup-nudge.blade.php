@extends('mail.layouts.simple')

@php
    $reminder = (bool) ($reminder ?? false);

    $title = $reminder
        ? 'Finish setting up the Ghostable CLI'
        : 'Get started with the Ghostable CLI';

    $preheader = $reminder
        ? 'Still haven’t installed the CLI? It only takes a minute and unlocks secure push/pull of env vars.'
        : "Hey {$name}, let’s install the Ghostable CLI so you can manage your secrets from your terminal.";

    $cta = $reminder ? 'Finish CLI setup' : 'Install the Ghostable CLI';

    // Absolute URLs for email clients
    $installUrl = 'https://docs.ghostable.dev/#installing-the-ghostable-cli';
    $docsUrl    = 'https://docs.ghostable.dev';
    $repoUrl    = 'https://github.com/ghostable-dev/cli';

    // Optional: UTM tags for tracking (remove if not needed)
    $installUrl .= $reminder
        ? '?utm_source=email&utm_medium=drip&utm_campaign=cli-setup&variant=reminder'
        : '?utm_source=email&utm_medium=drip&utm_campaign=cli-setup&variant=first';
@endphp

@section('title', $title)

@section('preheader')
    {{ $preheader }}
@endsection

@section('content')

    <x-mail.simple.title size="lg">{{ $title }}</x-mail.simple.title>

    <x-mail.simple.paragraph>
        @if($reminder)
            You’ve created your Ghostable account but haven’t installed the CLI yet. The CLI lets you securely
            pull, push, and sync environment variables across projects — right from your terminal. Most teams finish
            setup in about a minute.
        @else
            Hey <strong>{{ $name }}</strong>, the fastest way to start managing secrets is with the Ghostable CLI.
            With a single command you can securely pull, push, and sync environment variables across your projects.
        @endif
    </x-mail.simple.paragraph>

    <x-mail.simple.button :href="$installUrl">
        {{ $cta }}
    </x-mail.simple.button>

    <x-mail.simple.paragraph>
        @if($reminder)
            Need help getting started? Follow the <a href="{{ $installUrl }}">step-by-step guide</a> or check the 
            <a href="{{ $docsUrl }}">Ghostable docs</a> for additional resources.
        @else
            Want to dig deeper? Browse the <a href="{{ $docsUrl }}">Ghostable docs</a> for guides and examples,
            or check out the <a href="{{ $repoUrl }}">CLI on GitHub</a>.
        @endif
    </x-mail.simple.paragraph>

@endsection