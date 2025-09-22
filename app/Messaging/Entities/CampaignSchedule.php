<?php

namespace App\Messaging\Entities;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class CampaignSchedule extends Data
{
    private const UTC = 'UTC';

    public function __construct(
        /** Quiet hours in UTC (0–23). Always define these in UTC. */
        public array $quietHoursUtc = [0, 1, 2, 3, 4, 5, 6],

        /** Default tz if recipient has none */
        public string $defaultTimezone = self::UTC
    ) {}

    public function isQuietNow(?CarbonImmutable $now = null, ?string $tz = null): bool
    {
        $tz ??= $this->defaultTimezone;

        $now ??= CarbonImmutable::now($tz);

        $utcHour = (int) $now->setTimezone(self::UTC)->format('G');

        return in_array($utcHour, $this->quietHoursUtc, true);
    }

    public function allowSendNowFor(Model $recipient, ?CarbonImmutable $now = null): bool
    {
        $tz = $recipient->timezone ?? $this->defaultTimezone;

        return ! $this->isQuietNow($now, $tz);
    }
}
