@extends('mail.layouts.simple')

@section('title', 'Your Ghostable license')

@section('preheader')
    Your Ghostable {{ $plan_label }} license is ready.
@endsection

@section('content')

    <x-mail.simple.title>Your Ghostable license is ready</x-mail.simple.title>

    <x-mail.simple.paragraph>
        Thanks for purchasing a Ghostable license. Copy the key below into Ghostable Desktop to activate it.
    </x-mail.simple.paragraph>

    <x-mail.simple.paragraph>
        Plan: <strong>{{ $plan_label }}</strong>
    </x-mail.simple.paragraph>

    <div style="margin:0 0 40px;padding:18px 20px;border-radius:12px;background:#ffffff;color:#171717;font-size:18px;line-height:26px;font-weight:600;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace;word-break:break-all;">
        {{ $license_key }}
    </div>

    @if($is_guest_purchase)
        <x-mail.simple.button href="{{ $claim_url }}">Save license to an account</x-mail.simple.button>
    @else
        <x-mail.simple.button href="{{ $billing_url }}">View licenses</x-mail.simple.button>
    @endif

    <x-mail.simple.paragraph>
        You do not need an account to use this license. Saving it to an account is optional and makes team management and future recovery easier.
    </x-mail.simple.paragraph>

@endsection
