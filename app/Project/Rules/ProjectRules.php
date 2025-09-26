<?php

namespace App\Project\Rules;

use App\Organization\Models\Organization;
use App\Project\Enums\DeploymentProvider;
use App\Project\Models\Project;
use Illuminate\Validation\Rule;

class ProjectRules
{
    public static function createRules(Organization $organization): array
    {
        return [
            'name' => self::nameRules(),
            'deployment_provider' => self::deploymentProviderRules(),
        ];
    }

    public static function updateRules(Project $project): array
    {
        return [
            'name' => self::nameRules(),
            'description' => self::descriptionRules(),
            'deployment_provider' => self::deploymentProviderRules(),
        ];
    }

    public static function nameRules(): array
    {
        return ['required', 'string', 'max:100'];
    }

    public static function descriptionRules(): array
    {
        return ['nullable', 'string', 'max:250'];
    }

    public static function deploymentProviderRules(): array
    {
        return [Rule::enum(DeploymentProvider::class)];
    }
}
