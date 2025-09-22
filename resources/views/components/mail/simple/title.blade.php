@props([
    'size' => 'lg', // default
])

@php
    switch ($size) {
        case 'sm':
            $fontSize = '28px';
            $lineHeight = '32px';
            break;
        case 'md':
            $fontSize = '36px';
            $lineHeight = '40px';
            break;
        default: // 'lg'
            $fontSize = '48px';
            $lineHeight = '48px';
            break;
    }
@endphp

<h1 style="
    color:#171717;
    color:var(--text,#171717);
    margin:0 0 30px;
    font-size:{{ $fontSize }};
    line-height:{{ $lineHeight }};
    font-weight:400;
    font-family:-apple-system,BlinkMacSystemFont,'Albert Sans',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif;
    text-wrap:balance;
">
    {{ $slot }}
</h1>