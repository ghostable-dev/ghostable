<?php

use App\Api\Core\Http\Middleware\TrackUsage;
use App\Api\V1\Http\Controllers\Auth\LoginViaCli;
use App\Api\V1\Http\Controllers\Environment\CreateEnvironment;
use App\Api\V1\Http\Controllers\Environment\DeployEnvironment;
use App\Api\V1\Http\Controllers\Environment\DeployProviderEnvironment;
use App\Api\V1\Http\Controllers\Environment\DiffEnvironment;
use App\Api\V1\Http\Controllers\Environment\GetEnvFileFormats;
use App\Api\V1\Http\Controllers\Environment\GetEnvironment;
use App\Api\V1\Http\Controllers\Environment\GetEnvironmentTypes;
use App\Api\V1\Http\Controllers\Environment\PullEnvironment;
use App\Api\V1\Http\Controllers\Environment\PushEnvironment;
use App\Api\V1\Http\Controllers\Environment\ValidateEnvironment;
use App\Api\V1\Http\Controllers\Organization\GetOrganization;
use App\Api\V1\Http\Controllers\Organization\GetOrganizationRoles;
use App\Api\V1\Http\Controllers\Organization\GetOrganizations;
use App\Api\V1\Http\Controllers\Organization\GetOwnedOrganizations;
use App\Api\V1\Http\Controllers\Organization\InviteMember;
use App\Api\V1\Http\Controllers\Project\CreateProject;
use App\Api\V1\Http\Controllers\Project\GenerateSuggestedEnvironmentNames;
use App\Api\V1\Http\Controllers\Project\GetEnvironments;
use App\Api\V1\Http\Controllers\Project\GetProject;
use App\Api\V1\Http\Controllers\Project\GetProjects;
use App\Api\V1\Http\Controllers\Secret\CreateEnvironmentSecret;
use App\Api\V1\Http\Controllers\Secret\GetEnvironmentSecret;
use App\Api\V1\Http\Controllers\Secret\GetEnvironmentSecrets;
use App\Api\V1\Http\Controllers\Secret\GetSecretTypes;
use App\Api\V1\Http\Controllers\Secret\UpdateEnvironmentSecret;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Per-domain routes for API version v1 will be defined here.
|
*/

Route::middleware('api.version:v1')->group(function () {
    Route::post('/cli/login', LoginViaCli::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/organization-roles', GetOrganizationRoles::class);
        Route::get('/organizations', GetOrganizations::class);
        Route::get('/owned-organizations', GetOwnedOrganizations::class);
        Route::get('/organizations/{organization}', GetOrganization::class);
        Route::post('/organizations/{organization}/invite', InviteMember::class);

        Route::middleware(TrackUsage::class)->group(function () {

            Route::get('/ci/deploy', DeployEnvironment::class);
            Route::post('/ci/deploy/provider', DeployProviderEnvironment::class);

            Route::get('/organizations/{organization}/projects', GetProjects::class);
            Route::post('/organizations/{organization}/projects', CreateProject::class);
            Route::get('/projects/{project}', GetProject::class);
            Route::get('/projects/{project}/environments', GetEnvironments::class);
            Route::post('projects/{project}/environments', CreateEnvironment::class);

            Route::prefix('projects/{project}/environments/{name}')
                ->group(function () {
                    Route::get('/', GetEnvironment::class);
                    Route::post('/push', PushEnvironment::class);
                    Route::post('/diff', DiffEnvironment::class);
                    Route::get('/pull', PullEnvironment::class);
                    Route::post('/validate', ValidateEnvironment::class);
                    Route::get('/secrets', GetEnvironmentSecrets::class);
                    Route::post('/secrets', CreateEnvironmentSecret::class);
                    Route::get('/secrets/{secret}', GetEnvironmentSecret::class);
                    Route::put('/secrets/{secret}', UpdateEnvironmentSecret::class);
                });
        });

        Route::post('/projects/{project}/generate-suggested-environment-names', GenerateSuggestedEnvironmentNames::class);
        Route::get('/environment-types', GetEnvironmentTypes::class);
        Route::get('/environment-formats', GetEnvFileFormats::class);
        Route::get('/secret-types', GetSecretTypes::class);
    });
});
