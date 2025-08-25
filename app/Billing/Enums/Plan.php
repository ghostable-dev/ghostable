<?php

namespace App\Billing\Enums;

enum Plan: string
{
    case FREE = 'free';
    case STARTER = 'starter';
    case GROWTH = 'growth';
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
            self::FREE => 'Free',
            self::STARTER => 'Starter',
            self::GROWTH => 'Growth',
            self::GROWTH => 'Enterprise'
        };
    }

    public function isFree(): bool
    {
        return $this->is(self::FREE);
    }

    public function isStarter(): bool
    {
        return $this->is(self::STARTER);
    }

    public function isGrowth(): bool
    {
        return $this->is(self::GROWTH);
    }

    public function isEnterprise(): bool
    {
        return $this->is(self::ENTERPRISE);
    }

    public function is(self $plan): bool
    {
        return $this === $plan;
    }
}
