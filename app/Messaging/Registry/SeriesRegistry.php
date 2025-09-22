<?php

namespace App\Messaging\Registry;

use App\Messaging\Series\CampaignSeries;
use InvalidArgumentException;

class SeriesRegistry
{
    /** @var array<string, CampaignSeries> */
    private array $series = [];

    public function register(CampaignSeries $series): void
    {
        $this->series[$series->name] = $series;
    }

    public function get(string $name): CampaignSeries
    {
        return $this->series[$name] ?? throw new InvalidArgumentException("Unknown series [$name]");
    }

    /** @return array<string, CampaignSeries> */
    public function all(): array
    {
        return $this->series;
    }
}
