@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    {{ $removedUser->email }} was removed from the {{ $organization->name }} organization in Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        <strong>{{ $removedUser->email }}</strong> was removed from the
        <strong>{{ $organization->name }}</strong> organization in Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
