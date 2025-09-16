@props([
    'color' => "color:#737373; color:var(--muted, #737373);"
])
<p @style([
    "margin:0 0 40px;font-size:16px;line-height:24px;",
    "font-family:-apple-system,BlinkMacSystemFont,'Albert Sans',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif;",
    $color
])>
{{ $slot }}
</p>