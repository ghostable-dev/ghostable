<?php

namespace App\Messaging\Actions;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Core\Enums\NotificationCategory;
use App\Messaging\Builders\MessageBuilder;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Enums\CampaignType;
use Illuminate\Database\Eloquent\Builder;

class GetCampaignAudience
{
    public function handle(Campaign $campaign): array
    {
        return match ($campaign->kind()) {
            CampaignType::DRIP_USERS,
            CampaignType::BROADCAST_USERS => [
                $this->finalize($campaign, $this->applyCategories($campaign, $this->userBase())),
            ],

            CampaignType::BROADCAST_LIST => [
                $this->finalize($campaign, $this->applyCategories($campaign, $this->listBase())),
            ],

            CampaignType::BROADCAST_ALL => [
                $this->finalize($campaign, $this->applyCategories($campaign, $this->userBase())),
                $this->finalize($campaign, $this->applyCategories($campaign, $this->listBase())),
            ],
        };
    }

    protected function userBase(): Builder
    {
        return User::query()->verified();
    }

    protected function listBase(): Builder
    {
        return MailingListEmail::query();
    }

    protected function applyCategories(Campaign $campaign, Builder $query): Builder
    {
        foreach ($campaign->categories() as $category) {
            if ($category === NotificationCategory::BLOG) {
                $query->receivesBlogNotifications();
            }
            if ($category === NotificationCategory::RESEARCH) {
                $query->receivesResearchNotifications();
            }
            if ($category === NotificationCategory::PROMOTIONAL) {
                $query->receivesPromotionalNotifications();
            }
            if ($category === NotificationCategory::PRODUCT_TIPS) {
                $query->receivesProductTips();
            }
        }

        return $query;
    }

    protected function finalize(Campaign $campaign, Builder $query): Builder
    {
        $query->whereDoesntHave('messages', fn (MessageBuilder $query) => $query->forCampaign($campaign));

        return $campaign->audience($query);
    }
}
