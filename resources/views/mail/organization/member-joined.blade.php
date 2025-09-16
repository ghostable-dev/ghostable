@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    {{ $invite->email }} joined the {{ $organization->name }} organization in Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        <strong>{{ $invite->email }}</strong> joined the
        <strong>{{ $organization->name }}</strong> organization in Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
