<?php

use App\Account\AccountRoutes;
use App\Auth\AuthRoutes;
use App\Billing\BillingRoutes;
use App\Blog\BlogRoutes;
use App\Environment\EnvironmentRoutes;
use App\Organization\OrganizationRoutes;
use App\Project\ProjectRoutes;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/pricing', function () {
    return view('pricing');
})->name('pricing');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

AccountRoutes::web();
OrganizationRoutes::web();
EnvironmentRoutes::web();
ProjectRoutes::web();
AuthRoutes::web();
BillingRoutes::web();
BlogRoutes::web();
