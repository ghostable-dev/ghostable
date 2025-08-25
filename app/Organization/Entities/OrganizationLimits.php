<?php

namespace App\Organization\Entities;

abstract class OrganizationLimits
{
    public function __construct(
        public readonly ?int $projects,
        public readonly ?int $environments_per_project,
        public readonly string $kind,
    ) {}

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'projects' => $this->projects,
            'environments_per_project' => $this->environments_per_project,
        ];
    }

    /**
     * Get default limits for this organization type.
     */
    abstract public static function defaults(): static;

    /**
     * Create instance from stored array overlays.
     */
    abstract public static function fromArray(?array $data): static;
}
