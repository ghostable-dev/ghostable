@props([
    'href',
    'color' => "color: #ffffff; color:var(--btn-text, #ffffff);",
    'background' => "background: #080808; background:var(--btn-bg, #080808);"
])
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 40px;width:100%;">
    <tr>
        <td align="center">
            <a href="{{ $href }}"
                @style([
                    "display:inline-block;text-decoration:none;",
                    "padding:16px 48px;font-size:16px;font-weight:600;border-radius:22px",
                    $color,
                    $background,
                    "font-family:-apple-system,BlinkMacSystemFont,'Albert Sans',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif;"
                ])>
                <span><!--[if mso]><i style="mso-font-width:480%;mso-text-raise:24" hidden>&#8202;&#8202;&#8202;&#8202;&#8202;</i><![endif]--></span>
                <span style="line-height:120%;mso-text-raise:12px;">
                    {{ $slot }}
                </span>
                <span><!--[if mso]><i style="mso-font-width:480%" hidden>&#8202;&#8202;&#8202;&#8202;&#8202;&#8203;</i><![endif]--></span>
            </a>
        </td>
    </tr>
</table>