@extends('mail.layouts.simple')

@section('title', 'Project created')

@section('preheader')
    A new project named {{ $project->name }} was created in the {{ $project->organization->name }} organization on Ghostable.
@endsection

@section('content')

    <x-mail.simple.title>Project created</x-mail.simple.title>

    <x-mail.simple.paragraph>
        A new project named <strong>{{ $project->name }}</strong> was created in the
        <strong>{{ $project->organization->name }}</strong> organization on Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.button href="{{ route('login') }}">
        Manage
    </x-mail.simple.button>

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
