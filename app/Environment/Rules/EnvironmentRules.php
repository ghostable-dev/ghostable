<?php

namespace App\Environment\Rules;

use App\Environment\Models\Environment;
use App\Project\Models\Project;

class EnvironmentRules
{
    public static function createRules(Project $project): array
    {
        return [
            'name' => self::nameRules($project),
            'type' => self::typeRules(),
        ];
    }

    public static function updateRules(Environment $environment): array
    {
        return [
            'name' => self::nameRules($environment->project, $environment),
            'type' => self::typeRules(),
        ];
    }

    public static function nameRules(Project $project, ?Environment $environment = null): array
    {
        return ['required', 'max:100', new UniqueEnvironmentName($project, $environment)];
    }

    public static function typeRules(): array
    {
        return ['required', new ValidEnvType];
    }
}
