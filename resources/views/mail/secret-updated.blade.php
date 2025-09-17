@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    The {{ $secret->name }} secret was updated on Ghostable.
@endsection

@section('content')

    <x-mail.simple.paragraph>
        The <strong>{{ $secret->name }}</strong> secret was updated on Ghostable.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
