<?php

namespace App\Organization\Entities;

abstract class OrganizationFeatures
{
    public function __construct(
        public readonly bool $audits,
        public readonly bool $integrations,
        public readonly bool $advanced_permissions,
        public readonly string $kind,
    ) {}

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'audits' => $this->audits,
            'integrations' => $this->integrations,
            'advanced_permissions' => $this->advanced_permissions,
        ];
    }

    /**
     * Get default feature flags for this organization type.
     */
    abstract public static function defaults(): static;

    /**
     * Create instance from stored array overlays.
     */
    abstract public static function fromArray(?array $data): static;
}
