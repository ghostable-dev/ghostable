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

    public function suggestedValues(): array
    {
        return match ($this) {
            self::APP_DEBUG => ['true', 'false'],
            self::APP_ENV => ['local', 'production', 'staging'],
            self::LOG_CHANNEL => ['stack', 'single', 'daily'],
            self::LOG_LEVEL => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
            self::CACHE_DRIVER => ['file', 'redis', 'memcached'],
            self::QUEUE_CONNECTION => ['sync', 'database', 'redis', 'sqs'],
            self::SESSION_DRIVER => ['file', 'cookie', 'database', 'redis'],
            self::MAIL_MAILER => ['smtp', 'sendmail', 'mailgun', 'ses'],
            self::MAIL_ENCRYPTION => ['tls', 'ssl'],
            self::DB_CONNECTION => ['mysql', 'pgsql', 'sqlite', 'sqlsrv'],
            self::DB_PORT => ['3306', '5432', '1433'],
            self::AWS_DEFAULT_REGION => ['us-east-1', 'us-west-2', 'eu-west-1'],
            default => [],
        };
    }

    public static function suggestedValuesFor(string $key): array
    {
        $enum = self::tryFrom($key);

        return $enum?->suggestedValues() ?? [];
    }
}