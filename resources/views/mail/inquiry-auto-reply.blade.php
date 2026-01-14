@extends('mail.layouts.simple')

@section('title', $title)

@section('content')
    <x-mail.simple.title>{{ $title }}</x-mail.simple.title>
    <x-mail.simple.paragraph>
        Thanks for reaching out to Ghostable. We have received your {{ strtolower($inquiryType) }} request.
    </x-mail.simple.paragraph>
    <x-mail.simple.paragraph>
        Your case number is <strong>{{ $caseId }}</strong>. Please include it in any follow-up.
    </x-mail.simple.paragraph>
    <x-mail.simple.paragraph>
        We take security and support requests seriously and review reports as quickly as possible.
        If you need to add more details, reply to this email or write to {{ $replyEmail }}.
    </x-mail.simple.paragraph>
@endsection
