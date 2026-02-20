<?php

use App\Api\Core\Http\Middleware\TrackUsage;
use App\Api\V2\Http\Controllers\Auth\LoginViaCli;
use App\Api\V2\Http\Controllers\CliLogin\PollCliLogin;
use App\Api\V2\Http\Controllers\CliLogin\StartCliLogin;
use App\Api\V2\Http\Controllers\CliRegister\PollCliRegister;
use App\Api\V2\Http\Controllers\CliRegister\StartCliRegister;
use App\Api\V2\Http\Controllers\Device\DeleteDevice;
use App\Api\V2\Http\Controllers\Device\RegisterDevice;
use App\Api\V2\Http\Controllers\Device\RevokeDevice;
use App\Api\V2\Http\Controllers\Device\ShowDevice;
use App\Api\V2\Http\Controllers\Environment\CreateEnvironment;
use App\Api\V2\Http\Controllers\Environment\CreateEnvironmentBackup;
use App\Api\V2\Http\Controllers\Environment\CreateEnvironmentKey;
use App\Api\V2\Http\Controllers\Environment\CreateEnvironmentKeyEnvelope;
use App\Api\V2\Http\Controllers\Environment\DeleteEnvironment;
use App\Api\V2\Http\Controllers\Environment\DeleteEnvironmentVariable;
use App\Api\V2\Http\Controllers\Environment\DeployEnvironment;
use App\Api\V2\Http\Controllers\Environment\GetEnvFileFormats;
use App\Api\V2\Http\Controllers\Environment\GetEnvironmentDevices;
use App\Api\V2\Http\Controllers\Environment\GetEnvironmentHistory;
use App\Api\V2\Http\Controllers\Environment\GetEnvironmentKey;
use App\Api\V2\Http\Controllers\Environment\GetEnvironmentKeys;
use App\Api\V2\Http\Controllers\Environment\GetEnvironmentTypes;
use App\Api\V2\Http\Controllers\Environment\GetEnvironmentVariableHistory;
use App\Api\V2\Http\Controllers\Environment\GetEnvironmentVariableHistoryValue;
use App\Api\V2\Http\Controllers\Environment\PullEnvironment;
use App\Api\V2\Http\Controllers\Environment\PushEnvironment;
use App\Api\V2\Http\Controllers\Environment\RollbackEnvironmentVariable;
use App\Api\V2\Http\Controllers\Environment\UpdateEnvironment;
use App\Api\V2\Http\Controllers\Environment\UpdateEnvironmentVariable;
use App\Api\V2\Http\Controllers\Organization\CreateOrganization;
use App\Api\V2\Http\Controllers\Organization\DeleteOrganizationInvite;
use App\Api\V2\Http\Controllers\Organization\GetOrganization;
use App\Api\V2\Http\Controllers\Organization\GetOrganizationActivity;
use App\Api\V2\Http\Controllers\Organization\GetOrganizationInvites;
use App\Api\V2\Http\Controllers\Organization\GetOrganizationMembers;
use App\Api\V2\Http\Controllers\Organization\GetOrganizationRoles;
use App\Api\V2\Http\Controllers\Organization\GetOrganizations;
use App\Api\V2\Http\Controllers\Organization\GetOwnedOrganizations;
use App\Api\V2\Http\Controllers\Organization\InviteMember;
use App\Api\V2\Http\Controllers\Organization\UpdateOrganization;
use App\Api\V2\Http\Controllers\Project\CreateProject;
use App\Api\V2\Http\Controllers\Project\DeleteProject;
use App\Api\V2\Http\Controllers\Project\DeploymentToken\CreateDeploymentToken;
use App\Api\V2\Http\Controllers\Project\DeploymentToken\DeleteDeploymentToken;
use App\Api\V2\Http\Controllers\Project\DeploymentToken\ListDeploymentTokens;
use App\Api\V2\Http\Controllers\Project\DeploymentToken\RevokeDeploymentToken;
use App\Api\V2\Http\Controllers\Project\DeploymentToken\RotateDeploymentToken;
use App\Api\V2\Http\Controllers\Project\GenerateSuggestedEnvironmentNames;
use App\Api\V2\Http\Controllers\Project\GetEnvironments;
use App\Api\V2\Http\Controllers\Project\GetProject;
use App\Api\V2\Http\Controllers\Project\GetProjectActivity;
use App\Api\V2\Http\Controllers\Project\GetProjectHistory;
use App\Api\V2\Http\Controllers\Project\GetProjects;
use App\Api\V2\Http\Controllers\Project\GetProjectStackOptions;
use App\Api\V2\Http\Controllers\Project\UpdateProject;
use App\Api\V2\Http\Controllers\User\GetCurrentUser;
use App\Api\V2\Http\Controllers\User\ListUserDevices;
use App\Api\V2\Http\Controllers\User\UpdateCurrentUser;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2.x Routes
|--------------------------------------------------------------------------
|
| Per-domain routes for API version v2.x will be defined here.
|
*/

Route::middleware('api.version:v2')->group(function () {
    Route::prefix('cli')->group(function () {
        Route::prefix('login')->group(function () {
            Route::post('start', StartCliLogin::class);
            Route::post('poll', PollCliLogin::class);
            Route::post('/', LoginViaCli::class);
        });

        Route::prefix('register')->group(function () {
            Route::post('start', StartCliRegister::class);
            Route::post('poll', PollCliRegister::class);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', GetCurrentUser::class);
        Route::patch('user', UpdateCurrentUser::class);
        Route::get('user/devices', ListUserDevices::class);
        Route::get('organization-roles', GetOrganizationRoles::class);
        Route::get('owned-organizations', GetOwnedOrganizations::class);

        Route::prefix('organizations')->group(function () {
            Route::get('/', GetOrganizations::class);
            Route::post('/', CreateOrganization::class);
            Route::get('{organization}', GetOrganization::class);
            Route::get('{organization}/activity', GetOrganizationActivity::class);
            Route::get('{organization}/members', GetOrganizationMembers::class);
            Route::get('{organization}/invites', GetOrganizationInvites::class);
            Route::patch('{organization}', UpdateOrganization::class);
            Route::post('{organization}/invite', InviteMember::class);
            Route::delete('{organization}/invites/{invite}', DeleteOrganizationInvite::class);

            Route::middleware(TrackUsage::class)->group(function () {
                Route::get('{organization}/projects', GetProjects::class);
                Route::post('{organization}/projects', CreateProject::class);
            });
        });

        Route::prefix('devices')->group(function () {
            Route::post('/', RegisterDevice::class);
            Route::get('{device}', ShowDevice::class);
            Route::delete('{device}', RevokeDevice::class);
            Route::delete('{device}/delete', DeleteDevice::class);
        });

        Route::prefix('projects/{project}')->group(function () {
            Route::post('generate-suggested-environment-names', GenerateSuggestedEnvironmentNames::class);

            Route::middleware(TrackUsage::class)->group(function () {
                Route::delete('/', DeleteProject::class);
                Route::get('/', GetProject::class);
                Route::patch('/', UpdateProject::class);
                Route::get('environments', GetEnvironments::class);
                Route::patch('environments/{environment}', UpdateEnvironment::class);
                Route::delete('environments/{environment}', DeleteEnvironment::class);
                Route::get('activity', GetProjectActivity::class);
                Route::post('environments', CreateEnvironment::class);
                Route::get('history', GetProjectHistory::class);

                Route::prefix('deploy-tokens')->group(function () {
                    Route::get('/', ListDeploymentTokens::class);
                    Route::post('/', CreateDeploymentToken::class);
                    Route::post('{deploymentToken}/rotate', RotateDeploymentToken::class);
                    Route::post('{deploymentToken}/revoke', RevokeDeploymentToken::class);
                    Route::delete('{deploymentToken}', DeleteDeploymentToken::class);
                });

                Route::prefix('environments/{name}')->group(function () {
                    Route::post('push', PushEnvironment::class);
                    Route::get('pull', PullEnvironment::class);
                    Route::post('backups', CreateEnvironmentBackup::class);
                    Route::get('history', GetEnvironmentHistory::class);
                    Route::get('variables/{variable}/history', GetEnvironmentVariableHistory::class);
                    Route::get('variables/{variable}/history/{versionId}/value', GetEnvironmentVariableHistoryValue::class);
                    Route::patch('variables/{variable}', UpdateEnvironmentVariable::class);
                    Route::post('variables/{variable}/rollback', RollbackEnvironmentVariable::class);
                    Route::delete('variables/{variable}', DeleteEnvironmentVariable::class);

                    // kek management
                    Route::post('key', CreateEnvironmentKey::class);
                    Route::post('key/envelopes', CreateEnvironmentKeyEnvelope::class);
                    Route::get('key', GetEnvironmentKey::class);

                    // This was getting just an array of variable "key" names.
                    Route::get('keys', GetEnvironmentKeys::class);
                    Route::get('devices', GetEnvironmentDevices::class);
                });
            });
        });

        Route::middleware(TrackUsage::class)->group(function () {
            Route::get('ci/deploy', DeployEnvironment::class);
        });

        Route::get('project-stack-options', GetProjectStackOptions::class);
        Route::get('environment-types', GetEnvironmentTypes::class);
        Route::get('environment-formats', GetEnvFileFormats::class);
    });
});
