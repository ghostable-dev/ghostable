<?php

namespace App\Organization\Entities;

use App\Billing\Enums\Plan;
use Spatie\LaravelData\Data;

class OrganizationFeatures extends Data
{
    public function __construct(
        public readonly bool $audits = false,
        public readonly bool $integrations = false,
        public readonly bool $audit_webhooks = false,
        public readonly bool $advanced_permissions = false,
        public readonly bool $guided_key_reshare_v2 = true,
    ) {}

    /**
     * Create an OrganizationFeatures instance based on the given Plan.
     *
     * @param  Plan  $plan  The billing plan to resolve features for.
     */
    public static function fromPlan(Plan $plan): self
    {
        return match ($plan) {
            Plan::FREE => new self,
            Plan::STANDARD => new self(audits: true, integrations: true, audit_webhooks: false, advanced_permissions: false),
            Plan::SCALE => new self(audits: true, integrations: true, audit_webhooks: true, advanced_permissions: true),
            Plan::ENTERPRISE => new self(audits: true, integrations: true, audit_webhooks: true, advanced_permissions: true),
        };
    }

    /**
     * Merge in override values to customize features.
     *
     * @return self A new instance with overrides applied.
     */
    public function withOverrides(array $overrides): self
    {
        return new self(
            audits: $overrides['audits'] ?? $this->audits,
            integrations: $overrides['integrations'] ?? $this->integrations,
            audit_webhooks: $overrides['audit_webhooks'] ?? $this->audit_webhooks,
            advanced_permissions: $overrides['advanced_permissions'] ?? $this->advanced_permissions,
            // Guided key re-share is now globally enabled.
            guided_key_reshare_v2: true,
        );
    }
}
