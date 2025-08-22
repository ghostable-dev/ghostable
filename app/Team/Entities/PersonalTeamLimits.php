<?php

namespace App\Team\Entities;

final class PersonalTeamLimits extends TeamLimits
{
    public static function defaults(): static
    {
        $config = config('ghostable.personal_limits');

        return new self(
            projects: $config['projects'],
            environments_per_project: $config['environments_per_project'],
            kind: 'personal',
        );
    }

    public static function fromArray(?array $data): static
    {
        $defaults = static::defaults();
        $data = $data ?? [];

        return new static(
            projects: $data['projects'] ?? $defaults->projects,
            environments_per_project: $data['environments_per_project'] ?? $defaults->environments_per_project,
            kind: 'personal',
        );
    }
}
