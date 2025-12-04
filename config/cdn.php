<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public CDN
    |--------------------------------------------------------------------------
    |
    | Base URL for publicly served assets (images, downloads, etc.). The
    | default preserves the previous MAIL_CDN value for compatibility.
    |
    */

    'url' => env('CDN_URL', env('MAIL_CDN', 'https://fls-9fe4cb31-f981-461b-b08f-6d8b2fed8bbf.laravel.cloud')),

];
