<?php

namespace App\Project;

use App\Project\Livewire\ProjectView;
use Illuminate\Support\Facades\Route;

class ProjectRoutes
{
    public static function web(): void
    {
        Route::middleware(['auth', 'verified'])->group(function () {
            Route::get('projects/{project}', ProjectView::class)->name('projects.view');
        });
    }
}
