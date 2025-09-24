<?php

namespace App\Messaging\Jobs;

use App\Messaging\Enums\MessageStatus;
use App\Messaging\Middleware\ThrottleMailDelivery;
use App\Messaging\Models\Message;
use App\Messaging\Registry\CampaignRegistry;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $maxAttempts = 12;

    public $tries = 12;

    public function backoff(): array
    {
        return [5, 15, 30, 60, 120, 300];
    }

    public function __construct(public Message $message) {}

    public function middleware(): array
    {
        return [new ThrottleMailDelivery];
    }

    public function handle(CampaignRegistry $registry): void
    {
        // Hard stop → mark STALLED and remove the job without “failing” it
        if ($this->attempts() >= $this->maxAttempts) {
            $this->markFailed('max attempts reached');
            $this->delete();

            return;
        }

        // Resolve campaign
        try {
            $campaign = $registry->get($this->message->campaign_key);
        } catch (Exception $e) {
            $this->markFailed('Unknown campaign: '.$this->message->campaign_key);
            $this->delete();

            return;
        }

        // Send
        try {
            Mail::send($campaign->mailable($this->message->recipient));
        } catch (TransportExceptionInterface $e) {
            // Rate limit or temporary transport issue
            if ($this->isRateLimited($e)) {
                $this->release($this->retryAfter($e)); // requeue w/ provider hint

                return;
            }

            // Non-rate transient transport: back off + retry
            $this->release(max(1, $this->backoff()[min($this->attempts(), count($this->backoff()) - 1)]));

            return;
        } catch (Exception $e) {
            // Hard failure — mark FAILED and delete (don’t bubble)
            $this->markFailed($e->getMessage());
            $this->delete();

            return;
        }

        $this->message->status = MessageStatus::SENT;
        $this->message->sent_at = now();
        $this->message->save();
    }

    private function isRateLimited(TransportExceptionInterface $e): bool
    {
        $msg = strtolower($e->getMessage() ?? '');

        return str_contains($msg, 'rate') || str_contains($msg, '429');
    }

    private function retryAfter(TransportExceptionInterface $e): int
    {
        // If you can access a response, honor Retry-After. Otherwise default to 1–2s.
        // Adapt this if your transport exposes headers differently.
        try {
            if (method_exists($e, 'getResponse')) {
                $resp = $e->getResponse();
                if ($resp && ($h = $resp->getHeaders(false)) && ! empty($h['retry-after'][0])) {
                    return max(1, (int) $h['retry-after'][0]);
                }
            }
        } catch (\Throwable) {
        }

        return 1;
    }

    private function markFailed(string $reason): void
    {
        $this->message->status = MessageStatus::FAILED;
        $this->message->reason = $reason;
        $this->message->save();
    }
}
