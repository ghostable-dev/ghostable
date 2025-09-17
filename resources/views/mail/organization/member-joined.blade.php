@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    {{ $invite->email }} joined the {{ $organization->name }} organization on Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        <strong>{{ $invite->email }}</strong> joined the
        <strong>{{ $organization->name }}</strong> organization on Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
