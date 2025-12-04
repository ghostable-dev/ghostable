<?php

if (! function_exists('cdn_asset')) {
    function cdn_asset(string $path): string
    {
        $cdn = config('cdn.url') ?? config('mail.cdn');

        return rtrim((string) $cdn, '/').'/'.ltrim($path, '/');
    }
}
