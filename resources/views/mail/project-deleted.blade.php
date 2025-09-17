@extends('mail.layouts.simple')

@section('title', 'Project deleted')

@section('preheader')
    The project {{ $project->name }} was deleted from the {{ $project->organization->name }} organization on Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        The project <strong>{{ $project->name }}</strong> was deleted from the
        <strong>{{ $project->organization->name }}</strong> organization on Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
