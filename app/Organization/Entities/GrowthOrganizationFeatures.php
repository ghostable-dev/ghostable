<?php

namespace App\Organization\Entities;

final class GrowthOrganizationFeatures extends OrganizationFeatures
{
    public static function defaults(): static
    {
        return new self(
            audits: true,
            integrations: true,
            advanced_permissions: true,
            kind: 'growth',
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
            kind: 'growth',
        );
    }
}
