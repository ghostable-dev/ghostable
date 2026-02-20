<?php

namespace App\Project\Rules;

use App\Organization\Models\Organization;
use App\Project\Enums\DeploymentProvider;
use App\Project\Enums\ProjectStackTag;
use App\Project\Models\Project;
use Illuminate\Validation\Rule;

class ProjectRules
{
    public static function createRules(Organization $organization): array
    {
        return array_merge([
            'name' => self::nameRules(),
            'description' => self::descriptionRules(),
            'deployment_provider' => self::deploymentProviderRules(),
            'with_default_environments' => ['sometimes', 'boolean'],
        ], self::stackRules());
    }

    public static function updateRules(Project $project): array
    {
        return array_merge([
            'name' => self::nameRules(),
            'description' => self::descriptionRules(),
            'deployment_provider' => self::deploymentProviderRules(),
        ], self::stackRules());
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

    public static function stackRules(): array
    {
        $rules = [
            'stack' => ['nullable', 'array'],
            'stack.language' => ['nullable', Rule::in(ProjectStackTag::languageValues())],
            'stack.framework' => ['nullable', Rule::in(ProjectStackTag::frameworkValues())],
            'stack.platform' => ['nullable', Rule::in(ProjectStackTag::platformValues())],
        ];

        return $rules;
    }
}
