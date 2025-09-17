@extends('mail.layouts.simple')

@section('title', $title)

@section('preheader')
    {{ $message }}
@endsection

@section('content')

    <x-mail.simple.paragraph>
        {{ $message }}
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        You're receiving this message because you manage this organization in Ghostable.
    </x-mail.simple.paragraph>

@endsection
