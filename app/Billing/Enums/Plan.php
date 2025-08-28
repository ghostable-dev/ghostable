<?php

namespace App\Billing\Enums;

use Illuminate\Support\Collection;

enum Plan: string
{
    case FREE = 'free';
    case STANDARD = 'standard';
    case SCALE = 'scale';
    case ENTERPRISE = 'enterprise';
    
    /**
     * Get an array of plan values => labels.
     *
     * @param bool $billableOnly Limit to plans with Stripe billing config.
     * @return array<string, string>
     */
    public static function selectOptions(bool $billableOnly = false): array
    {
        return collect(self::cases())
            ->when($billableOnly, fn (Collection $plans) => $plans->filter(fn (self $plan) => $plan->isBillable())
            )->mapWithKeys(fn (self $plan) => [$plan->value => $plan->label()])
            ->toArray();
    }
    
    /**
     * Get all billable plans.
     *
     * @return self[]
     */
    public static function billable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $plan) => $plan->isBillable())
            ->values()
            ->toArray();
    }
    
    /**
     * Get the Plan enum from a Stripe API ID.
     */
    public static function tryFromBillableId(string $id): ?self
    {
        return collect(config('platform.billing'))
            ->filter(fn (array $config) => $config['api_id'] === $id)
            ->keys()
            ->map(fn (string $key) => self::tryFrom($key))
            ->first();
    }
    
    /**
     * Get the human-readable label for this plan.
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::STANDARD => 'Standard',
            self::SCALE => 'Scale',
            self::ENTERPRISE => 'Enterprise'
        };
    }

    /**
     * Get the Stripe API ID from config (null for non-billable plans).
     */
    public function getBillableId(): ?string
    {
        return config("platform.billing.{$this->value}.api_id");
    }
    
    /**
     * Determine if the plan is billable (exists in Stripe billing config).
     */
    public function isBillable(): bool
    {
        return config("platform.billing.{$this->value}") !== null;
    }
    
    /**
     * Check if plan is Free.
     */
    public function isFree(): bool
    {
        return $this->is(self::FREE);
    }
    
    /**
     * Check if plan is Standard.
     */
    public function isStandard(): bool
    {
        return $this->is(self::STANDARD);
    }
    
    /**
     * Check if plan is Scale.
     */
    public function isScale(): bool
    {
        return $this->is(self::SCALE);
    }
    
    /**
     * Check if plan is Enterprise.
     */
    public function isEnterprise(): bool
    {
        return $this->is(self::ENTERPRISE);
    }
    
    /**
     * Check if this plan matches the given plan.
     */
    public function is(self $plan): bool
    {
        return $this === $plan;
    }
}
