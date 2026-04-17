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
        Requester: <strong>{{ $requester_label }}</strong><br>
        Project: <strong>{{ $project_name }}</strong><br>
        Source: <strong>{{ $source_name }}</strong><br>
        Target: <strong>{{ $target_name }}</strong>
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        @if($includes_values)
            This request includes current encrypted values for transfer.
        @else
            This request promotes variable names only and leaves values empty.
        @endif
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        Promotion request ID: <code>{{ $request_model->id }}</code>
    </x-mail.simple.paragraph>

    @if(!empty($desktop_deep_link))
        <x-mail.simple.button href="{{ $desktop_deep_link }}">
            Open in Ghostable Desktop
        </x-mail.simple.button>
    @endif

    @if(!empty($request_model->id) && !empty($target_environment_url))
        <x-mail.simple.button href="{{ $target_environment_url }}">
            Open Target Environment
        </x-mail.simple.button>
    @endif

    <x-mail.simple.paragraph>
        You're receiving this message because you can edit variables for the target environment in "{{ $organization->name }}".
    </x-mail.simple.paragraph>

@endsection
