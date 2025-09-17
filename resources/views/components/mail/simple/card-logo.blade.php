@php($logoUrl = function_exists('mail_asset')
    ? mail_asset('logos/ghostable-dark@2x.v2.png')
    : asset('logos/ghostable-dark@2x.v2.png'))

<img
    src="{{ $logoUrl }}"
    alt="Ghostable"
    width="50" height="50"
    class="logo"
    @style([
        'background-color: #080808;',
        'border:1px solid #242424; border-radius:24px;',
        'display:inline-block; padding:10px; margin-bottom:60px;',
        'outline:none; border-collapse:collapse;',
    ])>