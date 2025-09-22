@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    {{ $invite->user->email }} invited {{ $invite->email }} to the {{ $organization->name }} organization on Ghostable.
@endsection

@section('content')

    <x-mail.simple.title>{{ $title }}</x-mail.simple.title>

    <x-mail.simple.paragraph>
        <strong>{{ $invite->user->email }}</strong> invited
        <strong>{{ $invite->email }}</strong> to the
        <strong>{{ $organization->name }}</strong> organization on Ghostable.
    </x-mail.simple.paragraph>
    
    <x-mail.simple.button href="{{ route('login') }}">
        Manage
    </x-mail.simple.button>

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
