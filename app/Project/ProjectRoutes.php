<?php

namespace App\Project;

use App\Project\Livewire\ProjectAccessManager;
use App\Project\Livewire\ProjectActivity;
use App\Project\Livewire\ProjectEnvironmentsManager;
use App\Project\Livewire\ProjectGeneralSettings;
use App\Project\Livewire\ProjectNotificationsManager;
use Illuminate\Support\Facades\Route;

class ProjectRoutes
{
    public static function web(): void
    {
        Route::middleware(['auth', 'verified'])
            ->prefix('projects/{project}/')
            ->name('project.')
            ->group(function () {
                Route::get('environments', ProjectEnvironmentsManager::class)->name('environments');
                Route::get('activity', ProjectActivity::class)->name('activity');
                Route::prefix('settings/')
                    ->name('settings.')
                    ->group(function () {
                        Route::get('general', ProjectGeneralSettings::class)->name('general');
                        Route::get('access', ProjectAccessManager::class)->name('access');
                        Route::get('notifications', ProjectNotificationsManager::class)->name('notifications');
                    });
            });
    }
}
