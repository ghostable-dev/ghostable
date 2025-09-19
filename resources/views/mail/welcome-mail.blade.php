@extends('mail.layouts.simple')

@section('title', 'Welcome to Ghostable')

@section('preheader')
    Hey {{ $name }}, let's set up your Ghostable organization so you can start collaborating.
@endsection

@section('content')

    <x-mail.simple.paragraph color="color:#171717;color:var(--text, #171717);">
        Hey {{ $name }}, thanks for signing up for Ghostable. You're just one step away from creating the
        workspace where you and your team will manage every secret in one secure place.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        Create your first organization to unlock shared access controls, invite your teammates, and keep
        credentials organized as your projects grow.
    </x-mail.simple.paragraph>

    <x-mail.simple.button href="{{ route('dashboard') }}">
        Create an organization
    </x-mail.simple.button>

    <x-mail.simple.paragraph>
        Not sure what to name it? Most teams start with their company or product name. You can always update
        it later—what matters is getting a secure home for your secrets in place today.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        Need help or have questions? Just reply to this email and we'll be happy to help you get going.
    </x-mail.simple.paragraph>

@endsection
