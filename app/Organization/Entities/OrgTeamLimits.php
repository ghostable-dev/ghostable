<?php

namespace App\Organization\Entities;

final class OrgOrganizationLimits extends OrganizationLimits
{
    public static function defaults(): static
    {
        $config = config('ghostable.org_defaults');

        return new self(
            projects: $config['projects'],
            environments_per_project: $config['environments_per_project'],
            kind: 'org',
        );
    }

    public static function fromArray(?array $data): static
    {
        $defaults = static::defaults();
        $data = $data ?? [];

        return new static(
            projects: $data['projects'] ?? $defaults->projects,
            environments_per_project: $data['environments_per_project'] ?? $defaults->environments_per_project,
            kind: 'org',
        );
    }
}
