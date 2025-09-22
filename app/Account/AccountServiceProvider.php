<?php

namespace App\Account;

use App\Account\Console\Commands\AppSetup;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AccountServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user' => 'App\Account\Models\User',
            'mailing_list_email' => 'App\Account\Models\MailingListEmail',
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                AppSetup::class,
            ]);
        }
    }
}
