<?php

namespace App\Integration\Integrations\Slack;

use App\Organization\Models\Organization;

interface SlackNotifiable
{
    /**
     * Returns the Slack message payload as a string or array.
     */
    public function toSlack(object $notifiable): string|array;

    /**
     * Returns the associated organization context for this notification.
     *
     * Used to resolve the organization's Slack webhook or delivery settings.
     */
    public function forOrganization(): Organization;
}
