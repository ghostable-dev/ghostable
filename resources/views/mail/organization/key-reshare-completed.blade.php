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
        The key re-share request for <strong>{{ $environment_name }}</strong> in
        <strong>{{ $project_name }}</strong> has been completed.
    </x-mail.simple.paragraph>

    @if(!empty($fulfilled_by_email))
        <x-mail.simple.paragraph>
            Completed by <strong>{{ $fulfilled_by_email }}</strong>.
        </x-mail.simple.paragraph>
    @endif

    <x-mail.simple.paragraph>
        Open Ghostable and refresh your environment view. Your device should now be able to read the latest variables.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You're receiving this message because this device requested environment key access in "{{ $organization->name }}".
    </x-mail.simple.paragraph>

@endsection
