<?php

use App\Api\Http\Controllers\Auth\LoginViaCli;
use App\Api\Http\Controllers\Environment\CreateEnvironment;
use App\Api\Http\Controllers\Environment\DeployEnvironment;
use App\Api\Http\Controllers\Environment\DiffEnvironment;
use App\Api\Http\Controllers\Environment\GetEnvFileFormats;
use App\Api\Http\Controllers\Environment\GetEnvironment;
use App\Api\Http\Controllers\Environment\GetEnvironmentTypes;
use App\Api\Http\Controllers\Environment\PullEnvironment;
use App\Api\Http\Controllers\Environment\PushEnvironment;
use App\Api\Http\Controllers\Environment\ValidateEnvironment;
use App\Api\Http\Controllers\Project\CreateProject;
use App\Api\Http\Controllers\Project\GenerateSuggestedEnvironmentNames;
use App\Api\Http\Controllers\Project\GetEnvironments;
use App\Api\Http\Controllers\Project\GetProject;
use App\Api\Http\Controllers\Project\GetProjects;
use App\Api\Http\Controllers\Team\GetOwnedTeams;
use App\Api\Http\Controllers\Team\GetTeam;
use App\Api\Http\Controllers\Team\GetTeamRoles;
use App\Api\Http\Controllers\Team\GetTeams;
use App\Api\Http\Controllers\Team\InviteTeamMember;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2 Routes
|--------------------------------------------------------------------------
|
| Per-domain routes for API version v2 will be defined here.
|
*/

Route::middleware('api.version:v2')->group(function () {
    Route::post('/cli/login', LoginViaCli::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/team-roles', GetTeamRoles::class);
        Route::get('/teams', GetTeams::class);
        Route::get('/owned-teams', GetOwnedTeams::class);
        Route::get('/teams/{team}', GetTeam::class);
        Route::post('/teams/{team}/invite', InviteTeamMember::class);

        Route::get('teams/{team}/projects', GetProjects::class);
        Route::get('/projects/{project}', GetProject::class);
        Route::get('/projects/{project}/environments', GetEnvironments::class);
        Route::post('/projects/{project}/generate-suggested-environment-names', GenerateSuggestedEnvironmentNames::class);
        Route::post('teams/{team}/projects', CreateProject::class);

        Route::get('/ci/deploy', DeployEnvironment::class);

        Route::get('/environment-types', GetEnvironmentTypes::class);
        Route::get('/environment-formats', GetEnvFileFormats::class);

        Route::post('projects/{project}/environments', CreateEnvironment::class);

        Route::prefix('projects/{project}/environments/{name}')
            ->group(function () {
                Route::get('/', GetEnvironment::class);
                Route::post('/push', PushEnvironment::class);
                Route::post('/diff', DiffEnvironment::class);
                Route::get('/pull', PullEnvironment::class);
                Route::post('/validate', ValidateEnvironment::class);
            });
    });
});
