<?php

declare(strict_types=1);

use App\Api\V3\Http\Controllers\Licensing\ActivateLicense;
use App\Api\V3\Http\Controllers\Licensing\CheckLicenseUpdates;
use App\Api\V3\Http\Controllers\Licensing\CreateLicenseCheckout;
use App\Api\V3\Http\Controllers\Licensing\DeactivateLicense;
use App\Api\V3\Http\Controllers\Licensing\ValidateLicense;
use Illuminate\Support\Facades\Route;

Route::middleware('api.version:v3')->group(function (): void {
    Route::prefix('licenses')->group(function (): void {
        Route::post('checkout', CreateLicenseCheckout::class)
            ->middleware(['auth:sanctum', 'throttle:license-checkout']);

        Route::post('activate', ActivateLicense::class)
            ->middleware('throttle:license-activate');

        Route::post('validate', ValidateLicense::class)
            ->middleware('throttle:license-desktop');

        Route::post('deactivate', DeactivateLicense::class)
            ->middleware('throttle:license-deactivate');
    });

    Route::get('updates/check', CheckLicenseUpdates::class)
        ->middleware('throttle:license-desktop');
});
