@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    Secret "{{ $secret->name }}" was updated in Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        Secret <strong>{{ $secret->name }}</strong> was updated in Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
