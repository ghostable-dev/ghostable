@extends('mail.layouts.simple')

@section('title', 'Manage your Ghostable licenses')

@section('preheader')
    Your temporary Ghostable license management link.
@endsection

@section('content')

    <x-mail.simple.title>Manage your Ghostable {{ $license_count === 1 ? 'license' : 'licenses' }}</x-mail.simple.title>

    <x-mail.simple.paragraph>
        We received a request to manage {{ $license_count === 1 ? 'a license' : $license_count.' licenses' }} purchased with this email address.
    </x-mail.simple.paragraph>

    <x-mail.simple.button :href="$management_url">Manage {{ $license_count === 1 ? 'license' : 'licenses' }}</x-mail.simple.button>

    <x-mail.simple.paragraph>
        This link expires in {{ $expires_in_minutes }} minutes. If you did not request it, you can safely ignore this email.
    </x-mail.simple.paragraph>

@endsection
