<?php

namespace App\Environment\Actions;

use App\Environment\Enums\EnvironmentType;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Models\Environment;
use App\Project\Models\Project;

class CreateEnv
{
    public function handle(string $name, EnvironmentType $type, Project $project): Environment
    {
        $env = new Environment;
        $env->name = $name;
        $env->type = $type;
        $env->file_format = EnvFileFormat::ALPHABETICAL;
        $env->project()->associate($project);
        $env->save();

        return $env;
    }
}
