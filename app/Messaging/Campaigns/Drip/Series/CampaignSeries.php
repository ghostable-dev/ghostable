<?php

namespace App\Messaging\Campaigns\Drip\Series;

use App\Account\Models\User;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Registry\CampaignRegistry;

class CampaignSeries
{
    /**
     * @param  string  $name  Series name (e.g. "onboarding").
     * @param  SeriesStep[]  $steps  Ordered steps in this series.
     * @param  int  $maxWindowDays  Stop nudging after this window (0 = no limit).
     */
    public function __construct(
        public string $name,
        public array $steps,
        public int $maxWindowDays = 0,
    ) {}

    /**
     * @return string[]
     */
    public function keys(CampaignRegistry $registry): array
    {
        $all = [];
        foreach ($this->steps as $step) {
            $all = array_merge($all, $this->keysForStep($registry, $step));
        }

        return array_values(array_unique($all));
    }

    /**
     * Decide which campaign key (if any) should be sent to the given user right now.
     */
    public function nextKeyFor(User $user, CampaignRegistry $registry): ?string
    {
        if ($this->maxWindowDays > 0 && $user->created_at?->lt(now()->subDays($this->maxWindowDays))) {
            return null; // past the window, stop nudging
        }

        foreach ($this->steps as $step) {
            // If complete, move on to next step
            if (($step->isComplete)($user)) {
                continue;
            }

            // Enforce cooldown
            if ($this->recentlyContacted($user, $this->keysForStep($registry, $step), $step->cooldownDays)) {
                return null;
            }

            // Primary first
            $primaryKey = $this->keyOf($registry, $step->primary);
            if (! $this->hasMessage($user, $primaryKey)) {
                return $primaryKey;
            }

            // Then reminders
            foreach ($step->reminders as $remClass) {
                $remKey = $this->keyOf($registry, $remClass);
                if (! $this->hasMessage($user, $remKey)) {
                    return $remKey;
                }
            }

            // Not complete, already nudged, cooling down
            return null;
        }

        return null; // all steps complete
    }

    private function keyOf(CampaignRegistry $registry, string $campaignClass): string
    {
        /** @var Campaign $c */
        $c = app($campaignClass);

        return $c->key();
    }

    /** @return string[] */
    private function keysForStep(CampaignRegistry $registry, SeriesStep $step): array
    {
        $classes = [$step->primary, ...$step->reminders];

        return array_map(fn ($cls) => $this->keyOf($registry, $cls), $classes);
    }

    private function hasMessage(User $u, string $key): bool
    {
        return $u->messages()->where('campaign_key', $key)->exists();
    }

    private function recentlyContacted(User $u, array $keys, int $cooldownDays): bool
    {
        if ($cooldownDays <= 0) {
            return false;
        }

        return $u->messages()
            ->whereIn('campaign_key', $keys)
            ->where('created_at', '>=', now()->subDays($cooldownDays))
            ->exists();
    }
}
