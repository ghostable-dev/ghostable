<?php

namespace App\Messaging\Registry;

use App\Messaging\Contracts\Campaign;
use App\Messaging\Contracts\ResolvableBroadcast;
use InvalidArgumentException;

class CampaignRegistry
{
    /** @var array<string, Campaign> keyed by campaign key (static) */
    private array $instances = [];

    /** @var array<class-string<ResolvableBroadcast>> */
    private array $broadcastClasses = [];

    public function register(Campaign $campaign): void
    {
        $this->instances[$campaign->key()] = $campaign;
    }

    public function registerBroadcast(string $class): void
    {
        if (! is_subclass_of($class, ResolvableBroadcast::class)) {
            throw new InvalidArgumentException("$class must implement ResolvableBroadcast");
        }
        $this->broadcastClasses[] = $class;
    }

    public function get(string $key): Campaign
    {
        $key = trim($key);

        // 1) Static instance?
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // 2) Let broadcast classes try to resolve
        foreach ($this->broadcastClasses as $class) {
            /** @var class-string<ResolvableBroadcast> $class */
            if ($class::supportsKey($key)) {
                $campaign = $class::fromKey($key);

                return $campaign;
            }
        }

        throw new InvalidArgumentException("Unknown campaign [$key]");
    }

    /** @return array<string, Campaign> */
    public function all(): array
    {
        return $this->instances;
    }
}
