<?php

namespace App\Environment\Support;

use App\Environment\Models\Environment;
use UnitEnum;

class EnvironmentAuditProperties
{
    /**
     * Build a consistent metadata payload for environment activity logs.
     *
     * @return array<string, mixed>
     */
    public static function make(Environment $environment): array
    {
        $environment->loadMissing('project.organization');

        $project = $environment->project;
        $organization = $project?->organization;

        $type = $environment->type;
        $typeValue = $type instanceof UnitEnum
            ? ($type instanceof \BackedEnum ? $type->value : $type->name)
            : $type;

        return array_filter([
            'id' => (string) $environment->id,
            'name' => $environment->name,
            'type' => $typeValue,
            'project_id' => $project?->id ? (string) $project->id : null,
            'project_name' => $project?->name,
            'organization_id' => $organization?->id ? (string) $organization->id : null,
            'organization_name' => $organization?->name,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
