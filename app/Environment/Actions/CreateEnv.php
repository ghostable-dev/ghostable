<?php

namespace App\Environment\Actions;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Rules\WithinProjectEnvironmentCap;
use App\Project\Models\Project;
use Illuminate\Support\Facades\Validator;

class CreateEnv
{
    public function handle(
        string $name,
        EnvironmentType $type,
        Project $project,
        ?Environment $base = null
    ): Environment {
        Validator::make(
            ['environment_limit' => null],
            ['environment_limit' => [new WithinProjectEnvironmentCap($project)]],
        )->validate();

        $env = new Environment;
        $env->name = $name;
        $env->type = $type;
        $env->file_format = EnvFileFormat::ALPHABETICAL;

        $env->kek_salt = base64_encode(random_bytes(32));

        $env->project()->associate($project);

        if ($project->is_legacy) {
            $env->base()->associate($base);
        }

        $env->save();

        return $env;
    }
}
