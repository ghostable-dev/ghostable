<?php

namespace App\Integration\Integrations\Slack;

use Illuminate\Notifications\Notification;

class SlackChannel
{
    public function __construct(protected SlackClient $client) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof SlackNotifiable) {
            return;
        }

        $webhookUrl = $notification->forTeam()->routeNotificationForSlack();

        // dd($notification, $notifiable);

        if (! $webhookUrl) {
            return;
        }

        $message = $notification->toSlack($notifiable);

        $this->client->sendWebhook($webhookUrl, $message);
    }
}
