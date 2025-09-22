@extends('mail.layouts.simple')

@php
    $reminder = (bool) ($reminder ?? false);

    $title = $reminder
        ? 'Still need to invite your team?'
        : 'Invite your teammates to Ghostable';

    $preheader = $reminder
        ? 'You’re the only one in Ghostable right now. Bring your teammates in so they can manage secrets with you.'
        : "Hey {$name}, add your teammates to Ghostable so everyone can manage secrets and env vars together.";

    $cta = $reminder ? 'Send invites' : 'Invite your team';

    $membersUrl = url()->route('organization.settings.members', [], true);

    $membersUrl = \Illuminate\Support\Str::of($membersUrl)
        ->append('?utm_source=email&utm_medium=drip&utm_campaign=invite-members')
        ->when($reminder, fn ($s) => $s->append('&variant=reminder'), fn ($s) => $s->append('&variant=first'));
@endphp

@section('title', $title)

@section('preheader')
    {{ $preheader }}
@endsection

@section('content')

    <x-mail.simple.title size="lg">{{ $title }}</x-mail.simple.title>

    <x-mail.simple.paragraph>
        @if($reminder)
            You set up your Ghostable workspace but haven’t invited anyone yet. Collaborating in Ghostable keeps
            every change audited, lets teammates manage their own access, and avoids secrets living in chat threads.
        @else
            Getting your team into Ghostable means everyone can securely pull, push, and audit environment changes
            without sharing passwords. Invite the folks who manage infrastructure, deployments, or sensitive configs.
        @endif
    </x-mail.simple.paragraph>

    <x-mail.simple.button :href="$membersUrl">
        {{ $cta }}
    </x-mail.simple.button>

    <x-mail.simple.paragraph>
        @if($reminder)
            Not sure who to invite first? Start with the teammates who need access to production secrets or help ship
            deployments—Ghostable keeps their access scoped and every action logged.
        @else
            Each teammate gets their own account with fine-grained permissions and an audit trail. You can always
            adjust roles later, so it’s easy to start with the core engineering team today.
        @endif
    </x-mail.simple.paragraph>

@endsection
