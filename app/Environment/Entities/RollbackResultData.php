<?php

declare(strict_types=1);

namespace App\Environment\Entities;

use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;

final class RollbackResultData
{
    public function __construct(
        public readonly EnvironmentSecret $secret,
        public readonly EnvironmentSecretVersion $appliedFromVersion,
        public readonly EnvironmentSecretVersion $newSnapshot,
        public readonly int $previousHeadVersion,
    ) {}

    public function variableName(): string
    {
        return $this->secret->name;
    }

    public function rolledBackToVersion(): int
    {
        return (int) $this->appliedFromVersion->version;
    }

    public function newVersion(): int
    {
        return (int) $this->secret->version;
    }
}
