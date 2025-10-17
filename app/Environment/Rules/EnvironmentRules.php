<?php

namespace App\Environment\Rules;

use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Validation\Rule;

class EnvironmentRules
{
    public static function createRules(Project $project): array
    {
        $rules = [
            'name' => self::nameRules($project),
            'type' => self::typeRules(),
        ];

        $rules['base_id'] = $project->is_legacy
            ? [
                'nullable',
                'sometimes',
                Rule::exists('environments', 'id')
                    ->where(fn ($query) => $query->where('project_id', $project->id)),
            ]
            : [
                'nullable',
                'sometimes',
                function (string $attribute, $value, $fail) {
                    if (filled($value)) {
                        $fail('Base environments are not supported for this project.');
                    }
                },
            ];

        return $rules;
    }

    public static function updateRules(Environment $environment): array
    {
        return [
            'name' => self::nameRules($environment->project, $environment),
            'type' => self::typeRules(),
            'fileFormat' => self::formatRules(),
        ];
    }

    public static function updateBaseRules(Environment $environment): array
    {
        if (! $environment->project->is_legacy) {
            return [
                'base_id' => [
                    'nullable',
                    'sometimes',
                    function (string $attribute, $value, $fail) {
                        if (filled($value)) {
                            $fail('Base environments are not supported for this project.');
                        }
                    },
                ],
            ];
        }

        return [
            'base_id' => [
                'nullable',
                'sometimes',
                Rule::exists('environments', 'id')
                    ->where(fn ($query) => $query
                        ->where('project_id', $environment->project_id)
                        ->where('id', '!=', $environment->id)),
            ],
        ];
    }

    public static function nameRules(Project $project, ?Environment $environment = null): array
    {
        return [
            'required',
            'string',
            'max:100',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            new UniqueEnvironmentName($project, $environment),
        ];
    }

    public static function typeRules(): array
    {
        return ['required', new ValidEnvType];
    }

    public static function formatRules(bool $required = true): array
    {
        $rules = [$required ? 'required' : 'sometimes'];

        $rules[] = new ValidEnvFileFormat;

        return $rules;
    }
}
