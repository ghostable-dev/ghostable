@extends('mail.layouts.simple')

@section('title', 'Invitation to Ghostable')

@section('preheader')
    {{ $organization->owner->name }} ({{ $organization->owner->email }}) invited you to collaborate on the "{{ $organization->name }}" organization on Ghostable.
@endsection

@section('content')

    <x-mail.simple.title>Invitation to Ghostable</x-mail.simple.title>

    <x-mail.simple.paragraph>
        {{ $organization->owner->name }} ({{ $organization->owner->email }}) invited
        you to collaborate on the <strong>{{ $organization->name }}</strong> organization on Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.button href="{{ route('login') }}">Accept Invitation</x-mail.simple.button>

    <x-mail.simple.paragraph>
        This invitation will expire in {{ config('platform.invite.expiration_days') }} days.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        If you weren't expecting this invitation, you can safely ignore this email.
    </x-mail.simple.paragraph>

@endsection
