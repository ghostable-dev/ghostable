@extends('mail.layouts.simple')

@section('title', 'Project updated')

@section('preheader')
    Settings for the {{ $project->name }} project in the {{ $project->organization->name }} organization were updated on Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        Settings for the <strong>{{ $project->name }}</strong> project in the
        <strong>{{ $project->organization->name }}</strong> organization were updated on Ghostable.
    </x-mail.simple.paragraph>

    {{-- <x-mail.simple.button href="{{ route('project.environments', ['project' => $project]) }}">View</x-mail.simple.button> --}}

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
