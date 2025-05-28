<?php

namespace App\Account\Providers;

use App\Account\Enums\Permission;
use App\Account\Managers\ACLManager;
use Illuminate\Support\ServiceProvider;

class ACLServiceProvider extends ServiceProvider
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_BILLING_MANAGER = 'billing';

    public const ROLE_DEV_READ_ONLY = 'dev_read_only';

    public const ROLE_DEV_READ_WRITE = 'dev_read_write';

    public const ROLE_CUSTOM = 'custom';

    public function register(): void {}

    public function boot(): void
    {
        ACLManager::defineRole(
            self::ROLE_ADMIN,
            'Admin',
            permissions: []
        )->description('Can manage everything — projects, environments, team members, and billing.');

        ACLManager::defineRole(
            self::ROLE_BILLING_MANAGER,
            'Billing Manager',
            permissions: [Permission::BillingManage]
        )->description('Handles billing, including subscriptions, invoices, and payment details.');

        ACLManager::defineRole(
            self::ROLE_DEV_READ_ONLY,
            'Developer (Read Only)',
            permissions: [Permission::EnvPull]
        )->description('Can access and pull environment variables, but not make changes.');

        ACLManager::defineRole(
            self::ROLE_DEV_READ_WRITE,
            'Developer (Read & Write)',
            permissions: [Permission::EnvPull, Permission::EnvPush, Permission::EnvCreate]
        )->description('Can create and update environment variables across team projects.');

        ACLManager::defineRole(
            self::ROLE_CUSTOM,
            'Custom',
            permissions: [Permission::EnvPull, Permission::EnvPush, Permission::EnvCreate]
        )->description('Permissions are manually configured per member.');
    }
}
