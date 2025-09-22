<?php

namespace App\Messaging\Campaigns\Drip\Series;

use App\Messaging\Campaigns\Drip\CliSetupNudge;
use App\Messaging\Campaigns\Drip\CliSetupReminder;
use App\Messaging\Campaigns\Drip\OrganizationSetupNudge;
use App\Messaging\Campaigns\Drip\OrganizationSetupReminder;

class OnboardingSeries
{
    /**
     * Build the "onboarding" procedural drip:
     *  - Step 1: Org setup (with reminder)
     *  - Step 2: CLI setup (with reminder)
     */
    public static function make(): CampaignSeries
    {
        return new CampaignSeries(
            name: 'onboarding',
            steps: [
                new SeriesStep(
                    primary: OrganizationSetupNudge::class,
                    reminders: [OrganizationSetupReminder::class],
                    cooldownDays: 2,
                    // complete when the user has at least one org
                    isComplete: fn ($u) => $u->organizations()->exists(),
                ),
                new SeriesStep(
                    primary: CliSetupNudge::class,
                    reminders: [CliSetupReminder::class],
                    cooldownDays: 3,
                    // complete when any personal access token exists
                    isComplete: fn ($u) => $u->tokens()->exists(),
                ),
            ],
            // stop nudging entirely after 30 days from signup (0 = no limit)
            maxWindowDays: 30,
        );
    }
}
