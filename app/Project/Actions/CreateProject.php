<?php

namespace App\Project\Actions;

use App\Project\Models\Project;
use App\Team\Models\Team;

class CreateProject
{
    public static function handle(string $name, Team $team): Project
    {
        $project = new Project;
        $project->name = $name;
        $project->team()->associate($team);
        $project->save();

        return $project;
    }
}
