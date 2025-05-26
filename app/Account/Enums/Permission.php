<?php

namespace App\Account\Enums;

enum Permission: string
{
    case EnvPush = 'env:push';
    case EnvPull = 'env:pull';
    case EnvCreate = 'env:create';
    case EnvUpdate = 'env:update';
    case EnvDelete = 'env:delete';
    case BillingManage = 'billing:manage';
    case MemberInvite = 'member:invite';
    case MemberRemove = 'member:remove';

    public function label(): string
    {
        return match ($this) {
            self::EnvPush   => 'Push environment files',
            self::EnvPull   => 'Pull environment files',
            self::EnvCreate => 'Create new environments',
            self::EnvUpdate => 'Update environment settings',
            self::EnvDelete => 'Delete environments',
            self::BillingManage => 'Manage billing and subscriptions',
            self::MemberInvite => 'Invite new team members',
            self::MemberRemove => 'Remove team members',
        };
    }
}