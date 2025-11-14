<?php

namespace App\Crypto;

use App\Crypto\Models\Device;
use App\Crypto\Models\Envelope;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CryptoServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::model('device', Device::class);
        Route::model('envelope', Envelope::class);

        Relation::enforceMorphMap([
            'envelope' => Envelope::class,
            'device' => Device::class,
        ]);
    }
}
