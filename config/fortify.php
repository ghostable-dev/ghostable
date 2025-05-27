<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'home' => '/dashboard',
    'middleware' => ['web'],
    'features' => [
        Features::twoFactorAuthentication(),
    ],
];
