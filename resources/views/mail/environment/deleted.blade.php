@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    Environment named {{ $environment->name }} was deleted from the {{ $environment->project->name }} project of the {{ $organization->name }} organization.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        Environment named <strong>{{ $environment->name }}</strong> was deleted from the
        <strong>{{ $environment->project->name }}</strong> project of the
        <strong>{{ $organization->name }}</strong> organization.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
