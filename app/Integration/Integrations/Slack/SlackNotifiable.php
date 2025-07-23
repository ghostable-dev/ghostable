<?php

namespace App\Integration\Integrations\Slack;

use App\Team\Models\Team;

interface SlackNotifiable
{
    /**
     * Returns the Slack message payload as a string or array.
     */
    public function toSlack(object $notifiable): string|array;

    /**
     * Returns the associated team context for this notification.
     *
     * Used to resolve the team's Slack webhook or delivery settings.
     */
    public function forTeam(): Team;
}
