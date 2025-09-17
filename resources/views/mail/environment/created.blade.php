@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    A new environment named {{ $environment->name }} was created for the {{ $environment->project->name }} project in the {{ $organization->name }} organization on Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        A new environment named <strong>{{ $environment->name }}</strong> was created for the
        <strong>{{ $environment->project->name }}</strong> project in the
        <strong>{{ $organization->name }}</strong> organization on Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
