<?php

namespace App\Project\Enums;

enum DeploymentProvider: string
{
    case LARAVEL_CLOUD = 'laravel_cloud';
    case LARAVEL_FORGE = 'laravel_forge';
    case LARAVEL_VAPOR = 'laravel_vapor';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LARAVEL_CLOUD => 'Laravel Cloud',
            self::LARAVEL_FORGE => 'Laravel Forge',
            self::LARAVEL_VAPOR => 'Laravel Vapor',
            self::OTHER => 'Other',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::LARAVEL_CLOUD => 'This project is hosted on Laravel Cloud.',
            self::LARAVEL_FORGE => 'This project is hosted via Laravel Forge on your servers.',
            self::LARAVEL_VAPOR => 'This project is serverless and managed by Laravel Vapor.',
            self::OTHER => 'This project uses a custom or third-party provider.',
        };
    }

    public function url(): ?string
    {
        return match ($this) {
            self::LARAVEL_CLOUD => 'https://laravel.com/cloud',
            self::LARAVEL_FORGE => 'https://forge.laravel.com',
            self::LARAVEL_VAPOR => 'https://vapor.laravel.com',
            self::OTHER => null,
        };
    }

    public function htmlDescription(): string
    {
        return match ($this) {
            self::LARAVEL_CLOUD => '<p>This project is hosted on <a href="https://laravel.com/cloud" target="_blank" rel="noopener noreferrer">Laravel Cloud</a>.</p>',
            self::LARAVEL_FORGE => '<p>This project is hosted via <a href="https://forge.laravel.com" target="_blank" rel="noopener noreferrer">Laravel Forge</a> on your servers.</p>',
            self::LARAVEL_VAPOR => '<p>This project is serverless and managed by <a href="https://vapor.laravel.com" target="_blank" rel="noopener noreferrer">Laravel Vapor</a>.</p>',
            self::OTHER => '<p>This project uses a custom or third-party provider.</p>',
        };
    }
}
