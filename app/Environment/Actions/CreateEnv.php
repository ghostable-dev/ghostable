<?php

namespace App\Environment\Actions;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Environment\Rules\WithinProjectEnvironmentCap;
use App\Project\Models\Project;
use Illuminate\Support\Facades\Validator;

class CreateEnv
{
    public function handle(
        string $name,
        EnvironmentType $type,
        Project $project
    ): Environment {
        Validator::make(
            ['environment_limit' => null],
            ['environment_limit' => [new WithinProjectEnvironmentCap($project)]],
        )->validate();

        $trashedEnvironments = Environment::query()
            ->onlyTrashed()
            ->where('project_id', $project->id)
            ->where('name', $name)
            ->get();

        $trashedEnvironments->each(function (Environment $environment): void {
            $environment->keys()
                ->with('envelope')
                ->cursor()
                ->each(function (EnvironmentKey $environmentKey): void {
                    $environmentKey->envelope()?->delete();
                    $environmentKey->delete();
                });

            EnvironmentKeyReshareRequest::query()
                ->where('environment_id', $environment->getKey())
                ->delete();
        });

        $env = new Environment;
        $env->name = $name;
        $env->type = $type;
        $env->file_format = EnvFileFormat::ALPHABETICAL;

        $env->project()->associate($project);

        $env->save();

        return $env;
    }
}
