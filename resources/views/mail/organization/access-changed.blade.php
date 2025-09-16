@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    Member "{{ $user->email }}" role was changed in the {{ $organization->name }} organization of Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        Member <strong>{{ $user->email }}</strong> role was changed in the
        <strong>{{ $organization->name }}</strong> organization of Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
