@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    Variable "{{ $variable->key }}" was updated in the {{ $environment->name }} environment of the {{ $organization->name }} organization.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        Variable <strong>{{ $variable->key }}</strong> was updated in the
        <strong>{{ $environment->name }}</strong> environment of the
        <strong>{{ $organization->name }}</strong> organization.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
