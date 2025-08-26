<?php

namespace App\Organization\Entities;

final class FreeOrganizationFeatures extends OrganizationFeatures
{
    public static function defaults(): static
    {
        return new self(
            audits: false,
            integrations: false,
            advanced_permissions: false,
            kind: 'free',
        );
    }

    public static function fromArray(?array $data): static
    {
        $defaults = static::defaults();
        $data = $data ?? [];

        return new static(
            audits: $data['audits'] ?? $defaults->audits,
            integrations: $data['integrations'] ?? $defaults->integrations,
            advanced_permissions: $data['advanced_permissions'] ?? $defaults->advanced_permissions,
            kind: 'free',
        );
    }
}
