<?php

namespace App\Billing\Enums;

enum SubscriptionType: string 
{
    case BUSINESS = 'business';
    case ENTERPRISE = 'enterprise';
    
    public static function tryFromApiId(string $value): ?static
    {
        return match ($value) {
            config("platform.billing.enterprise.api_id") => static::ENTERPRISE,
            config("platform.billing.business.api_id") => static::BUSINESS,
            default => null,
        };
    }
    
    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
            ->toArray();
    }
    
    public function label(): string
    {
        return match ($this) {
            self::BUSINESS => 'Business',
            self::ENTERPRISE => 'Enterprise'
        };
    }
    
    public function is(self $type): bool
    {
        return $this === $type;
    }
}