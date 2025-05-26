<?php

namespace App\Project;

use App\Project\Api\Controllers\ProjectController;
use App\Project\Livewire\ProjectView;
use App\Project\Livewire\TeamProjects;
use Illuminate\Support\Facades\Route;

class ProjectRoutes
{
    public static function api(): void
    {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('teams/{team}/projects', [ProjectController::class, 'index']);
            Route::get('/projects/{project}', [ProjectController::class, 'show']);
            Route::post('teams/{team}/projects', [ProjectController::class, 'store']);
        });
    }
    
    public static function web(): void
    {
        Route::get('projects/{project}', ProjectView::class)->name('projects.view');
    }
}
