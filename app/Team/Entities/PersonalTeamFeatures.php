<?php

namespace App\Team\Entities;

final class PersonalTeamFeatures extends TeamFeatures
{
    public static function defaults(): static
    {
        $config = config('ghostable.personal_features');

        return new self(
            audits: $config['audits'],
            integrations: $config['integrations'],
            advanced_permissions: $config['advanced_permissions'],
            kind: 'personal',
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
            kind: 'personal',
        );
    }
}
