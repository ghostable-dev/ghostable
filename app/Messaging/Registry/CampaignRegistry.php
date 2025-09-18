<?php

namespace App\Messaging\Registry;

use App\Messaging\Contracts\Campaign;
use InvalidArgumentException;

class CampaignRegistry
{
    /** @var array<string, Campaign> */
    private array $byKey = [];

    public function register(Campaign $campaign): void
    {
        $this->byKey[$campaign->key()] = $campaign;
    }

    public function get(string $key): Campaign
    {
        return $this->byKey[$key] ?? throw new InvalidArgumentException("Unknown campaign [$key]");
    }

    /** @return array<string, Campaign> */
    public function all(): array
    {
        return $this->byKey;
    }
}
