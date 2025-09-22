<?php

namespace App\Messaging\Concerns;

use App\Messaging\Models\Message;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\URL;

trait ReceivesMessages
{
    /**
     * Create unsubscribe link.
     */
    private function buildUnsubscribeLink(string $type, string $id): string
    {
        return URL::signedRoute(
            'notifications.unsubscribe', [
                'type' => $type,
                'id' => $id,
            ]
        );
    }

    /**
     * Polymorphic relation to the outbound messages ledger.
     */
    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'recipient');
    }

    /**
     * Latest message row for this recipient & campaign (or null).
     */
    public function latestMessageForCampaign(string $campaignKey): ?Message
    {
        return $this->messages()
            ->where('campaign_key', $campaignKey)
            ->latest('created_at')
            ->first();
    }

    /**
     * Quick per-status counts for this recipient.
     *
     * @return array<string,int>
     */
    public function messageStatusCounts(): array
    {
        return $this->messages()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();
    }
}
