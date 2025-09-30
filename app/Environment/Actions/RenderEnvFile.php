<?php

namespace App\Environment\Actions;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Models\Environment;

class RenderEnvFile
{
    public function handle(Environment $env, ?EnvFileFormat $format = null): string
    {
        $resolved = resolve(ResolveEnvironmentVariables::class)->handle($env);

        $format = $format ?? ($env->file_format ?? EnvFileFormat::ALPHABETICAL);

        return resolve(RenderEnvironmentVariables::class)->handle(variables: $resolved, format: $format);
    }
}
