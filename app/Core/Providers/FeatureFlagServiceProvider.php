<?php

declare(strict_types=1);

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class FeatureFlagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Feature::define('integrations', fn () => true);
    }
}
