<?php

if (! function_exists('mail_asset')) {
    function mail_asset(string $path): string
    {
        return rtrim(config('mail.cdn'), '/').'/'.ltrim($path, '/');
    }
}
