@extends('mail.layouts.simple')

@section('title', $headline)

@section('preheader')
    {{ $summary }}
@endsection

@section('content')

    <x-mail.simple.title>{{ $headline }}</x-mail.simple.title>

    @if(! empty($summary))
        <x-mail.simple.paragraph>
            {{ $summary }}
        </x-mail.simple.paragraph>
    @endif

    @if(! empty($details))
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;text-align:left;border-collapse:collapse;">
            @foreach($details as $label => $value)
                <tr>
                    <td style="padding:8px 12px 8px 0;font-size:14px;line-height:20px;color:#737373;font-family:-apple-system,BlinkMacSystemFont,'Albert Sans',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif;white-space:nowrap;">
                        {{ $label }}
                    </td>
                    <td style="padding:8px 0;font-size:14px;line-height:20px;color:#171717;font-family:-apple-system,BlinkMacSystemFont,'Albert Sans',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif;">
                        {{ $value }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

@endsection
