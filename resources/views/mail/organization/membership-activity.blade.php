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
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
