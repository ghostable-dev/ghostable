<?php

namespace App\Environment\Deployment\Entities;

use App\Project\Enums\DeploymentProvider;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class DeploymentData extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class)]
        public DeploymentProvider $provider,
        public ?Collection $standard = null,
        public ?Collection $secret = null,
        public ?string $encrypted = null,
    ) {}
}
