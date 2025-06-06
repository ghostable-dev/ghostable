<?php

namespace App\Project\Rules;

use App\Project\Models\Project;
use App\Team\Models\Team;

class ProjectRules
{
    public static function createRules(Team $team): array
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
