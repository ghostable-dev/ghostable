<?php

namespace App\Messaging\Jobs;

use App\Messaging\Enums\MessageStatus;
use App\Messaging\MessagingServiceProvider;
use App\Messaging\Models\Message;
use App\Messaging\Registry\CampaignRegistry;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMessage implements ShouldQueue
{
    use Queueable;

    public $tries = 25;

    public function __construct(public Message $message) {}

    public function middleware(): array
    {
        return [(new RateLimited(MessagingServiceProvider::MAIL_GLOBAL_LIMITER))->releaseAfter(1)];
    }

    public function handle(CampaignRegistry $registry): void
    {
        try {
            $campaign = $registry->get($this->message->campaign_key);
        } catch (Exception $e) {
            Log::error('Unknown campaign: '.$this->message->campaign_key);

            return;
        }

        try {
            Mail::send($campaign->mailable($this->message->recipient));
        } catch (Exception $e) {
            $this->message->status = MessageStatus::FAILED;
            $this->message->reason = $e->getMessage();
            $this->message->save();

            return;
        }

        $this->message->status = MessageStatus::SENT;
        $this->message->sent_at = now();
        $this->message->save();
    }
}
