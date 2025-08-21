<?php

namespace App\Team;

use App\Team\Livewire\TeamBillingSettings;
use App\Team\Livewire\TeamGeneralSettings;
use App\Team\Livewire\TeamMemberSettings;
use Illuminate\Support\Facades\Route;

class TeamRoutes
{
    public static function web(): void
    {
        Route::prefix('team/settings')
            ->name('team.settings.')
            ->middleware(['auth', 'verified'])
            ->group(function () {
                Route::redirect('/', 'settings/general')->name('index');
                Route::get('general', TeamGeneralSettings::class)->name('general');
                Route::get('members', TeamMemberSettings::class)->name('members');
                Route::get('notifications', \App\Team\Livewire\TeamNotificationsSettings::class)
                    ->name('notifications');
                Route::get('billing', TeamBillingSettings::class)->name('billing');
            });

        // Route::prefix('/account/{account}/settings')
        //     ->name('account.settings.')
        //     ->group(function() {
        //         Route::view('/credits', 'account.settings.credits')
        //             ->name('credits')->can('manageBilling', 'account');
        //         Route::view('/billing', 'account.settings.billing')
        //             ->name('billing')->can('manageBilling', 'account');
        //         Route::view('/integrations', 'account.settings.integrations')
        //             ->name('integrations')->can('admin', 'account');
        //     });
    }
}
