<?php

namespace App\Messaging\Campaigns\Drip\Series;

use App\Messaging\Campaigns\Drip\CliSetupNudge;
use App\Messaging\Campaigns\Drip\CliSetupReminder;
use App\Messaging\Campaigns\Drip\InviteMembersNudge;
use App\Messaging\Campaigns\Drip\InviteMembersReminder;
use App\Messaging\Campaigns\Drip\OrganizationSetupNudge;
use App\Messaging\Campaigns\Drip\OrganizationSetupReminder;
use App\Organization\Enums\InviteStatus;
use Illuminate\Database\Eloquent\Builder;

class OnboardingSeries
{
    /**
     * Build the "onboarding" procedural drip:
     *  - Step 1: Org setup (with reminder)
     *  - Step 2: CLI setup (with reminder)
     *  - Step 3: Invite members (with reminder)
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
                new SeriesStep(
                    primary: InviteMembersNudge::class,
                    reminders: [InviteMembersReminder::class],
                    cooldownDays: 4,
                    // complete when the user has invited or added teammates
                    isComplete: fn ($u) => $u->organizations()
                        ->where(function (Builder $orgs) use ($u) {
                            $orgs->whereHas('users', fn (Builder $users) => $users->whereKeyNot($u->getKey()))
                                ->orWhereHas('invites', fn (Builder $invites) => $invites->whereIn('status', [
                                    InviteStatus::PENDING->value,
                                    InviteStatus::ACCEPTED->value,
                                ]));
                        })
                        ->exists(),
                ),
            ],
            // stop nudging entirely after 30 days from signup (0 = no limit)
            maxWindowDays: 30,
        );
    }
}
