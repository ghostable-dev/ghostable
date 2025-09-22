<?php

namespace App\Messaging\Actions;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Enums\MessageStatus;
use App\Messaging\Models\Message;

class CreateMessageRecord
{
    public function handle(
        string $campaignKey,
        User|MailingListEmail $recipient,
        bool $allowDuplicate = false,
    ): Message {
        $message = $this->firstOrCreateMessage(campaignKey: $campaignKey, recipient: $recipient);

        if (! $allowDuplicate && ! $message->wasRecentlyCreated) {
            return $message;
        }

        return $message;
    }

    protected function firstOrCreateMessage(string $campaignKey, User|MailingListEmail $recipient): Message
    {
        return Message::firstOrCreate([
            'campaign_key' => $campaignKey,
            'recipient_type' => $recipient->getMorphClass(),
            'recipient_id' => $recipient->getKey(),
        ], [
            'recipient_email' => $recipient->email,
            'status' => MessageStatus::QUEUED,
            'queued_at' => now(),
        ]);
    }
}
