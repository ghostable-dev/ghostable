<?php

namespace App\Environment\Actions;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Rules\WithinProjectEnvironmentCap;
use App\Project\Models\Project;
use Illuminate\Encryption\Encrypter;
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

        $env->encryption_key = base64_encode(
            Encrypter::generateKey(config('app.cipher')),
        );

        $env->project()->associate($project);
        $env->base()->associate($base);
        $env->save();

        return $env;
    }
}
