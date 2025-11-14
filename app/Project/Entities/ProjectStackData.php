<?php

namespace App\Project\Entities;

use App\Project\Enums\ProjectStackTag;
use Spatie\LaravelData\Data;

class ProjectStackData extends Data
{
    public function __construct(
        public ?ProjectStackTag $language = null,
        public ?ProjectStackTag $framework = null,
        public ?ProjectStackTag $platform = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'language' => $this->language?->value,
            'framework' => $this->framework?->value,
            'platform' => $this->platform?->value,
        ], static fn ($value) => $value !== null);
    }
}
