@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    Organization settings for {{ $organization->name }} were updated in Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        Organization settings for <strong>{{ $organization->name }}</strong> were updated in Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
