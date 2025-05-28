<?php

return [

    /**
     * Team invites.
     */
    'invite' => [
        'resend_cooldown_minutes' => env('INVITE_RESEND_COOLDOWN_MINUTES', 5),
        'expiration_days' => env('INVITE_EXPIRATION_DAYS', 7),
    ],
];
