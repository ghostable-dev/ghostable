<?php

namespace App\Billing\Enums;

enum Plan: string 
{
    case PERSONAL = 'personal';
    case BUSINESS = 'business';
    case ENTERPRISE = 'enterprise';
    
    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($plan) => [$plan->value => $plan->label()])
            ->toArray();
    }
    
    public function label(): string
    {
        return match ($this) {
            self::PERSONAL => 'Personal',
            self::BUSINESS => 'Business',
            self::ENTERPRISE => 'Enterprise'
        };
    }
    
    public function isPersonal(): bool
    {
        return $this->is(static::PERSONAL);
    }
    
    public function isBusiness(): bool
    {
        return $this->is(static::BUSINESS);
    }
    
    public function isEnterprise(): bool
    {
        return $this->is(static::ENTERPRISE);
    }
    
    public function is(self $plan): bool
    {
        return $this === $plan;
    }
}
