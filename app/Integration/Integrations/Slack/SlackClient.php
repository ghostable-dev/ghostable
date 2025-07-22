<?php

namespace App\Integration\Integrations\Slack;

use Illuminate\Support\Facades\Http;

class SlackClient
{
    public function sendWebhook(string $webhookUrl, string $message): void
    {
        if (! $webhookUrl) {
            return;
        }

        Http::post($webhookUrl, [
            'text' => $message,
        ]);
    }
}
