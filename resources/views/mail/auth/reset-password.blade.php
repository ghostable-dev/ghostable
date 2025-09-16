@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    You requested to reset your Ghostable password.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        You are receiving this email because we received a password reset request for your Ghostable account.
    </x-mail.simple.paragraph>

    <x-mail.simple.button href="{{ $url }}">Reset Password</x-mail.simple.button>

    <x-mail.simple.paragraph>
        This link will expire in {{ $expiration }} minutes.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        If you did not request a password reset, no further action is required.
    </x-mail.simple.paragraph>

@endsection
