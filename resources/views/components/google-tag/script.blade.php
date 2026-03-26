@props([
    'id',
    'event' => null,
    'payload' => [],
])

<script async src="https://www.googletagmanager.com/gtag/js?id={{ $id }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];

    function gtag(){dataLayer.push(arguments);}

    gtag('js', new Date());
    gtag('config', @js($id));

    @if(filled($event))
        gtag('event', @js($event), @js($payload));
    @endif
</script>
