@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    {{ $summary_line }}
@endsection

@section('content')

    <x-mail.simple.title>{{ $title }}</x-mail.simple.title>

    <x-mail.simple.paragraph>
        {{ $summary_line }}
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        A new device was linked to an environment, and it needs key access before it can decrypt secrets.
        For security, Ghostable keeps environment keys device-scoped and encrypted, so an existing authorized
        device must re-share the key.
    </x-mail.simple.paragraph>

    @if($first_request)
        <x-mail.simple.paragraph>
            First pending request:
            <strong>{{ $first_request->environment?->name ?? 'Unknown environment' }}</strong>
            for
            <strong>{{ $first_request->targetUser?->email ?? 'Unknown user' }}</strong>
            (device: {{ $first_request->targetDevice?->name ?? $first_request->target_device_id }}).
        </x-mail.simple.paragraph>
    @endif

    @if(!empty($cli_command))
        <x-mail.simple.paragraph>
            Run the re-share from a device that already has access to this environment key.<br>
            CLI fallback command:<br>
            <code>{{ $cli_command }}</code>
        </x-mail.simple.paragraph>
    @endif

    <x-mail.simple.button href="{{ $dashboard_url }}">
        Open Key Re-share Requests
    </x-mail.simple.button>

    <x-mail.simple.paragraph>
        You're receiving this message because you can manage environment settings in this organization.
        If no authorized device is available, another admin with existing key access can fulfill this request.
    </x-mail.simple.paragraph>

@endsection
