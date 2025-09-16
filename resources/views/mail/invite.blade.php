@extends('mail.layouts.simple')

@section('title', "You're invited to join " . $organization->name)

@section('preheader')
    {{ $organization->owner->name }} ({{ $organization->owner->email }}) has invited you to collaborate on the "{{ $organization->name }}" organization in Ghostable.
@endsection

@section('content')
    
    <x-mail.simple.paragraph>
        {{ $organization->owner->name }} ({{ $organization->owner->email }}) has invited 
        you to collaborate on the <strong>{{ $organization->name }}</strong> organization in Ghostable.
    </x-mail.simple.paragraph>
    
    <x-mail.simple.button href="{{ route('login') }}">Accept Invitation</x-mail.simple.button>

    <x-mail.simple.paragraph>
        This invitation will expire in {{ config('platform.invite.expiration_days') }} days.
    </x-mail.simple.paragraph>

@endsection
