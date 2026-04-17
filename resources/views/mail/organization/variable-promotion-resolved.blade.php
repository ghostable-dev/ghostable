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
        Your promotion request in <strong>{{ $project_name }}</strong> was {{ $resolution_text }} by
        <strong>{{ $actor_label }}</strong>.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        Source: <strong>{{ $source_name }}</strong><br>
        Target: <strong>{{ $target_name }}</strong><br>
        Variables: <strong>{{ $entry_count }}</strong><br>
        Status: <strong>{{ $status }}</strong>
    </x-mail.simple.paragraph>

    @if(!empty($reason))
        <x-mail.simple.paragraph>
            Reason: <strong>{{ $reason }}</strong>
        </x-mail.simple.paragraph>
    @endif

    <x-mail.simple.paragraph>
        Promotion request ID: <code>{{ $request_model->id }}</code>
    </x-mail.simple.paragraph>

    @if(!empty($target_environment_url))
        <x-mail.simple.button href="{{ $target_environment_url }}">
            Open Target Environment
        </x-mail.simple.button>
    @endif

    <x-mail.simple.paragraph>
        You're receiving this message because you created this promotion request in "{{ $organization->name }}".
    </x-mail.simple.paragraph>

@endsection
