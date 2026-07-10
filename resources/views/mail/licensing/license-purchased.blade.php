@extends('mail.layouts.simple')

@section('title', 'Your Ghostable license')

@section('preheader')
    Your Ghostable {{ $plan_label }} license for {{ $organization_name }} is ready.
@endsection

@section('content')

    <x-mail.simple.title>Your Ghostable license is ready</x-mail.simple.title>

    <x-mail.simple.paragraph>
        Thanks for purchasing a Ghostable license for <strong>{{ $organization_name }}</strong>.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        Plan: <strong>{{ $plan_label }}</strong>
    </x-mail.simple.paragraph>

    <div style="margin:0 0 40px;padding:18px 20px;border-radius:12px;background:#ffffff;color:#171717;font-size:18px;line-height:26px;font-weight:600;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;word-break:break-all;">
        {{ $license_key }}
    </div>

    <x-mail.simple.button href="{{ $billing_url }}">View licenses</x-mail.simple.button>

    <x-mail.simple.paragraph>
        Use this key in the Ghostable desktop app when activating your license. For security, the web app only shows a masked version after this email.
    </x-mail.simple.paragraph>

@endsection
