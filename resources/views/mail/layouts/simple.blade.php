<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="x-apple-disable-message-reformatting">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <meta name="supported-color-schemes" content="light dark">
        <title>@yield('title')</title>
        <style>:root{--bg:#f5f5f5;--text:#171717;--muted:#737373;--btn-bg:#080808;--btn-text:#ffffff}@media (prefers-color-scheme:dark){:root{--bg:#000000;--text:#ffffff;--muted:#9b9b9b;--btn-bg:#ffffff;--btn-text:#080808}}@media (max-width:600px){.container{padding:100px 20px}.h1{font-size:36px;line-height:40px}}</style>
    </head>
    <body role="article" aria-roledescription="email" style="margin:0;background:#f5f5f5;background:var(--bg, #f5f5f5);color:#171717;color:var(--text, #171717);">
      
        @hasSection('preheader')
            <div style="display:none;overflow:hidden;line-height:1px;opacity:0;max-height:0;max-width:0;">
                @yield('preheader')
            </div>
        @endif
      
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td align="center" bgcolor="#f5f5f5"
                    style="background:#f5f5f5;background:var(--bg,#f5f5f5);">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:500px;margin:0 auto;">
                        <tr>
                            <td class="container" style="padding:120px 20px;text-align:center;">
                      
                                <x-mail.simple.card-logo/>

                                <h1 class="h1" style="color:#171717;color:var(--text, #171717);margin:0 0 30px;font-size:48px;line-height:48px;font-weight:400;text-wrap:balance;font-family:-apple-system,BlinkMacSystemFont,'Albert Sans',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif;">
                                    @yield('title')
                                </h1>

                                @yield('content')
                                
                                <p style="margin-top:96px;font-size:12px;line-height:22px;color:#9b9b9b;color:var(--muted, #9b9b9b);font-family:-apple-system,BlinkMacSystemFont,'Albert Sans',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif;">
                                    {{ config('contact.address.line1') }}
                                    {{ config('contact.address.line2') }}<br>
                                    {{ config('contact.address.addressLocality') }} 
                                    {{ config('contact.address.addressRegion') }} 
                                    {{ config('contact.address.postalCode') }}
                                </p>
                                
                                <p style="margin-bottom:16px;font-size:12px;line-height:22px;color:#9b9b9b;color:var(--muted, #9b9b9b);font-family:-apple-system,BlinkMacSystemFont,'Albert Sans',Roboto,Oxygen,Ubuntu,Cantarell,'Fira Sans','Droid Sans','Helvetica Neue',sans-serif;">
                                    &copy; {{ date('Y') }} Ghostable. @lang('All rights reserved.')<br/>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>