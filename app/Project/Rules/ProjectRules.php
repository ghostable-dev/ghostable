<?php

namespace App\Project\Rules;

use App\Organization\Models\Organization;
use App\Project\Models\Project;

class ProjectRules
{
    public static function createRules(Organization $organization): array
    {
        return [
            'name' => self::nameRules(),
        ];
    }

    public static function updateRules(Project $project): array
    {
        return [
            'name' => self::nameRules(),
            'description' => self::descriptionRules(),
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
}
