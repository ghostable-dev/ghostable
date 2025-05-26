<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Project\Models\Project;

class CreateEnv
{
    public function handle(string $name, Project $project): Environment
    {
        $env = new Environment();
        $env->name = $name;
        $env->project()->associate($project);
        $env->save();
        
        return $env;
    }
}