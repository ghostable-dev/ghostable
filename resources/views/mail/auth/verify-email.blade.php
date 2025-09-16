@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    Confirm your email address to start using Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        Please click the button below to verify your email address and finish setting up your Ghostable account.
    </x-mail.simple.paragraph>

    <x-mail.simple.button href="{{ $url }}">Verify Email Address</x-mail.simple.button>

    <x-mail.simple.paragraph>
        If you did not create an account, you can safely ignore this email.
    </x-mail.simple.paragraph>

@endsection
