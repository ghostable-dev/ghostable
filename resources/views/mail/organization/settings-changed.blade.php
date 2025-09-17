@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    Settings for the {{ $organization->name }} organization were updated on Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        Settings for the <strong>{{ $organization->name }}</strong> organization were updated on Ghostable.
    </x-mail.simple.paragraph>
    
    <x-mail.simple.button href="{{ route('login') }}">
        Manage
    </x-mail.simple.button>

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
