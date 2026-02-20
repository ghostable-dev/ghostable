<?php

declare(strict_types=1);

$appName = (string) env('APP_NAME', 'Ghostable');
$appUrl = rtrim((string) env('APP_URL', 'http://localhost'), '/');

return [
    'title' => env('DESKTOP_UPDATE_TITLE', "{$appName} Desktop Updates"),
    'description' => env('DESKTOP_UPDATE_DESCRIPTION', 'Release feed for the Ghostable desktop app.'),
    'language' => env('DESKTOP_UPDATE_LANGUAGE', 'en-US'),
    'link' => env('DESKTOP_UPDATE_LINK', $appUrl),

    'channels' => [
        'stable' => [
            'version' => env('DESKTOP_UPDATE_STABLE_VERSION'),
            'short_version' => env('DESKTOP_UPDATE_STABLE_SHORT_VERSION'),
            'download_url' => env('DESKTOP_UPDATE_STABLE_URL'),
            'ed_signature' => env('DESKTOP_UPDATE_STABLE_ED_SIGNATURE'),
            'length' => (int) env('DESKTOP_UPDATE_STABLE_LENGTH', 0),
            'pub_date' => env('DESKTOP_UPDATE_STABLE_PUB_DATE'),
            'release_notes_url' => env('DESKTOP_UPDATE_STABLE_RELEASE_NOTES_URL'),
            'minimum_system_version' => env('DESKTOP_UPDATE_STABLE_MIN_SYSTEM_VERSION'),
            'title' => env('DESKTOP_UPDATE_STABLE_TITLE'),
            'description' => env('DESKTOP_UPDATE_STABLE_DESCRIPTION'),
        ],
        'beta' => [
            'version' => env('DESKTOP_UPDATE_BETA_VERSION'),
            'short_version' => env('DESKTOP_UPDATE_BETA_SHORT_VERSION'),
            'download_url' => env('DESKTOP_UPDATE_BETA_URL'),
            'ed_signature' => env('DESKTOP_UPDATE_BETA_ED_SIGNATURE'),
            'length' => (int) env('DESKTOP_UPDATE_BETA_LENGTH', 0),
            'pub_date' => env('DESKTOP_UPDATE_BETA_PUB_DATE'),
            'release_notes_url' => env('DESKTOP_UPDATE_BETA_RELEASE_NOTES_URL'),
            'minimum_system_version' => env('DESKTOP_UPDATE_BETA_MIN_SYSTEM_VERSION'),
            'title' => env('DESKTOP_UPDATE_BETA_TITLE'),
            'description' => env('DESKTOP_UPDATE_BETA_DESCRIPTION'),
        ],
    ],
];
