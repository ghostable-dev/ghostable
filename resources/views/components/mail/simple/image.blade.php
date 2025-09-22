@props([
    'src',
    'alt' => '',
    'width' => '100%',   // default full width,
    'paddingBottom' => '16px', // default bottom spacing
])

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0; padding: 0;">
    <tr>
        <td align="center" style="padding: 0; padding-bottom: {{ $paddingBottom }};">
            <img 
                src="{{ $src }}" 
                alt="{{ $alt }}" 
                width="{{ $width }}" 
                border="0"
                style="
                    display: block;
                    max-width: 100%;
                    height: auto;
                    border-radius: 8px;
                "
            />
        </td>
    </tr>
</table>