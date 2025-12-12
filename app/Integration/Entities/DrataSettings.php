<?php

declare(strict_types=1);

namespace App\Integration\Entities;

use Spatie\LaravelData\Data;

class DrataSettings extends Data
{
    public readonly string $base_url;

    public readonly string $mode;

    public function __construct(?string $base_url = null, string $mode = 'api_token')
    {
        $this->base_url = $base_url ?? (string) config('drata.base_url', 'https://api.drata.com');
        $this->mode = $mode;
    }

    public static function defaults(): self
    {
        return new self(
            base_url: (string) config('drata.base_url', 'https://api.drata.com'),
            mode: 'api_token',
        );
    }
}
