<?php

namespace App\Organization;

use App\Organization\Livewire\OrganizationBillingSettings;
use App\Organization\Livewire\OrganizationGeneralSettings;
use App\Organization\Livewire\OrganizationIntegrationsSettings;
use App\Organization\Livewire\OrganizationMemberSettings;
use App\Organization\Livewire\OrganizationNotificationsSettings;
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
                Route::get('notifications', OrganizationNotificationsSettings::class)->name('notifications');
                Route::get('billing', OrganizationBillingSettings::class)->name('billing');
                Route::get('integrations', OrganizationIntegrationsSettings::class)->name('integrations');
            });
    }
}
