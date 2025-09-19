<?php

namespace App\Messaging\Actions;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Enums\MessageStatus;
use App\Messaging\Models\Message;

class QueueCampaignEmail
{
    public function handle(
        string $campaignKey,
        User|MailingListEmail $recipient,
        bool $allowDuplicate = false,
    ): Message {
        $email = $recipient->email ?? null;

        if (! $email) {
            // Record suppression and exit
            return Message::create([
                'campaign_key' => $campaignKey,
                'recipient_type' => $recipient->getMorphClass(),
                'recipient_id' => $recipient->getKey(),
                'recipient_email' => '',
                'status' => MessageStatus::SUPPRESSED,
                'reason' => 'missing-email',
                'queued_at' => now(),
            ]);
        }

        // Create or fetch the ledger row (prevents duplicate sends per campaign/recipient)
        $message = Message::firstOrCreate([
            'campaign_key' => $campaignKey,
            'recipient_type' => $recipient->getMorphClass(),
            'recipient_id' => $recipient->getKey(),
        ], [
            'recipient_email' => $email,
            'status' => MessageStatus::QUEUED,
            'queued_at' => now(),
        ]);

        if (! $allowDuplicate && ! $message->wasRecentlyCreated) {
            // Already queued/sent for this campaign+recipient; just return the row
            return $message;
        }

        $message->update([
            'recipient_email' => $email,
        ]);

        return $message;
    }
}
