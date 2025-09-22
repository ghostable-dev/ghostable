<?php

namespace App\Messaging\Listeners;

use App\Messaging\Enums\MessageStatus;
use App\Messaging\Models\Message as MessageModel;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Str;

class MarkMessageAsSent
{
    public function handle(MessageSent $event): void
    {
        $headers = $event->message->getHeaders();

        $idHeader = $headers->get('X-Ghostable-Message-Id');
        $campaignHeader = $headers->get('X-Ghostable-Campaign');
        $providerIdHeader = $headers->get('Message-ID'); // Symfony/Mailer may set this; keep if useful

        if (! $idHeader) {
            return;
        }

        $messageId = (string) $idHeader->getBody();
        $campaign = $campaignHeader ? (string) $campaignHeader->getBody() : null;

        /** @var MessageModel|null $row */
        $row = MessageModel::find($messageId);
        if (! $row) {
            return;
        }

        $updates = [
            'status' => MessageStatus::SENT,
            'sent_at' => now(),
        ];

        // If your provider driver injects a provider Message-ID, keep it
        if ($providerIdHeader && Str::length((string) $providerIdHeader->getBody()) <= 128) {
            $updates['provider_message_id'] = (string) $providerIdHeader->getBody();
        }

        if ($campaign && $row->campaign_key !== $campaign) {
            $updates['campaign_key'] = $campaign; // sanity sync
        }

        $row->update($updates);
    }
}
