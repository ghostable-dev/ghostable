<?php

namespace App\Account\Enums;

enum Permission: string
{
    // Team & Billing
    case BillingManage = 'billing:manage';
    case MemberManage = 'member:manage';
    
    // Projects
    case ProjectCreate = 'project:create';
    case ProjectDelete = 'project:delete';
    case ProjectManage = 'project:manage';
    
    // Environments
    case EnvPull = 'env:pull';
    case EnvPush = 'env:push';
    case EnvUpdate = 'env:update';
    case EnvDelete = 'env:delete';
    case EnvCreate = 'env:create';
    
    public function label(): string
    {
        return match ($this) {
            // Environments
            self::EnvPull => 'View environment variables',
            self::EnvPush => 'Push full environment files',
            self::EnvUpdate => 'Edit environment variables',
            self::EnvDelete => 'Delete environment variables',
            self::EnvCreate => 'Create new environments',
            
            // Projects
            self::ProjectCreate => 'Create new projects',
            self::ProjectDelete => 'Delete projects',
            self::ProjectManage => 'Manage project settings',
            
            // Team & Billing
            self::BillingManage => 'Manage billing and subscriptions',
            self::MemberManage => 'Manage team members',
        };
    }
}