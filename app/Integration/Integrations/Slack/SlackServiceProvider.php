<?php

namespace App\Integration\Integrations\Slack;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class SlackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SlackClient::class);
        $this->app->singleton(SlackChannel::class);
    }

    public function boot(): void
    {
        Notification::extend('slack', function ($app) {
            return $app->make(SlackChannel::class);
        });
    }
}
