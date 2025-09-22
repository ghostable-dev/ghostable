@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    Use the button below to reset your Ghostable password.
@endsection

@section('content')

    <x-mail.simple.title>{{ $title }}</x-mail.simple.title>

    <x-mail.simple.paragraph>
        You're receiving this message because we received a password reset request for your Ghostable account.
    </x-mail.simple.paragraph>

    <x-mail.simple.button :href="$url">Reset Password</x-mail.simple.button>

    <x-mail.simple.paragraph>
        This link will expire in {{ $expiration }} minutes.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        If you didn't request a password reset, you can safely ignore this email.
    </x-mail.simple.paragraph>

@endsection
