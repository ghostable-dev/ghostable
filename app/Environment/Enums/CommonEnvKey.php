<?php

namespace App\Environment\Enums;

enum CommonEnvKey: string
{
    // App
    case APP_NAME = 'APP_NAME';
    case APP_ENV = 'APP_ENV';
    case APP_KEY = 'APP_KEY';
    case APP_DEBUG = 'APP_DEBUG';
    case APP_URL = 'APP_URL';

    // Logging
    case LOG_CHANNEL = 'LOG_CHANNEL';
    case LOG_LEVEL = 'LOG_LEVEL';

    // Database
    case DB_CONNECTION = 'DB_CONNECTION';
    case DB_HOST = 'DB_HOST';
    case DB_PORT = 'DB_PORT';
    case DB_DATABASE = 'DB_DATABASE';
    case DB_USERNAME = 'DB_USERNAME';
    case DB_PASSWORD = 'DB_PASSWORD';

    // Cache / Queues / Sessions
    case CACHE_DRIVER = 'CACHE_DRIVER';
    case QUEUE_CONNECTION = 'QUEUE_CONNECTION';
    case SESSION_DRIVER = 'SESSION_DRIVER';
    case SESSION_LIFETIME = 'SESSION_LIFETIME';

    // Mail
    case MAIL_MAILER = 'MAIL_MAILER';
    case MAIL_HOST = 'MAIL_HOST';
    case MAIL_PORT = 'MAIL_PORT';
    case MAIL_USERNAME = 'MAIL_USERNAME';
    case MAIL_PASSWORD = 'MAIL_PASSWORD';
    case MAIL_ENCRYPTION = 'MAIL_ENCRYPTION';

    // AWS
    case AWS_ACCESS_KEY_ID = 'AWS_ACCESS_KEY_ID';
    case AWS_SECRET_ACCESS_KEY = 'AWS_SECRET_ACCESS_KEY';
    case AWS_DEFAULT_REGION = 'AWS_DEFAULT_REGION';
    case AWS_BUCKET = 'AWS_BUCKET';

    // Pusher
    case PUSHER_APP_ID = 'PUSHER_APP_ID';
    case PUSHER_APP_KEY = 'PUSHER_APP_KEY';
    case PUSHER_APP_SECRET = 'PUSHER_APP_SECRET';

    public static function values(): array
    {
        return array_map(
            fn(self $key) => $key->value,
            self::cases()
        );
    }
}