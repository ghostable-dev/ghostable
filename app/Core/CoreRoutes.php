<?php

namespace App\Core;

use App\Core\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

class CoreRoutes
{
    public static function web(): void
    {
        Route::get('contact', [ContactController::class, 'create'])->name('contact');
        Route::post('contact', [ContactController::class, 'store'])
            ->middleware('throttle:contact');
    }
}
