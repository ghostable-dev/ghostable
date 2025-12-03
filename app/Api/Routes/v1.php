<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes (deprecated)
|--------------------------------------------------------------------------
|
| API v1 has been retired. All requests to this version return 410 Gone
| with a helpful migration message to steer clients to v2.
|
*/

Route::any('{any?}', function () {
    return response()->json([
        'message' => 'API v1 has been retired. Please upgrade to API v2.',
    ], 410);
})->where('any', '.*');
