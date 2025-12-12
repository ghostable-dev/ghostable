<?php

declare(strict_types=1);

namespace App\Integration\Entities;

use Spatie\LaravelData\Data;

class VantaSettings extends Data
{
    public readonly string $base_url;

    public readonly string $mode;

    public readonly string $scope;

    public readonly ?string $resource_id;

    public readonly bool $sync_users_enabled;

    public function __construct(
        ?string $base_url = null,
        string $mode = 'oauth',
        ?string $scope = null,
        ?string $resource_id = null,
        bool $sync_users_enabled = true,
    ) {
        $this->base_url = $base_url ?? (string) config('vanta.base_url', 'https://api.vanta.com');
        $this->mode = $mode;
        $this->scope = $scope ?? (string) config('vanta.default_scope', 'connectors.self:read-resource connectors.self:write-resource');
        $this->resource_id = $resource_id ?? config('vanta.resource_id');
        $this->sync_users_enabled = $sync_users_enabled;
    }

    public static function defaults(): self
    {
        return new self(
            base_url: (string) config('vanta.base_url', 'https://api.vanta.com'),
            mode: 'oauth',
            scope: (string) config('vanta.default_scope', 'connectors.self:read-resource connectors.self:write-resource'),
            resource_id: config('vanta.resource_id'),
            sync_users_enabled: true,
        );
    }
}
