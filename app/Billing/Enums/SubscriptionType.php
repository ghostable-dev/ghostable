<?php

namespace App\Billing\Enums;

enum SubscriptionType: string
{
    case STARTER = 'starter';
    case GROWTH = 'growth';
    case ENTERPRISE = 'enterprise';

    public static function tryFromApiId(string $value): ?static
    {
        return match ($value) {
            config('platform.billing.enterprise.api_id') => self::ENTERPRISE,
            config('platform.billing.growth.api_id') => self::GROWTH,
            config('platform.billing.starter.api_id') => self::STARTER,
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
            self::STARTER => 'Starter',
            self::GROWTH => 'Growth',
            self::ENTERPRISE => 'Enterprise'
        };
    }

    public function is(self $type): bool
    {
        return $this === $type;
    }
}
