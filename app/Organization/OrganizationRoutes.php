<?php

namespace App\Organization;

use App\Organization\Livewire\OrganizationBillingSettings;
use App\Organization\Livewire\OrganizationGeneralSettings;
use App\Organization\Livewire\OrganizationMemberSettings;
use Illuminate\Support\Facades\Route;

class OrganizationRoutes
{
    public static function web(): void
    {
        Route::prefix('organization/settings')
            ->name('organization.settings.')
            ->middleware(['auth', 'verified'])
            ->group(function () {
                Route::redirect('/', 'settings/general')->name('index');
                Route::get('general', OrganizationGeneralSettings::class)->name('general');
                Route::get('members', OrganizationMemberSettings::class)->name('members');
                Route::get('notifications', \App\Organization\Livewire\OrganizationNotificationsSettings::class)
                    ->name('notifications');
                Route::get('billing', OrganizationBillingSettings::class)->name('billing');
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
