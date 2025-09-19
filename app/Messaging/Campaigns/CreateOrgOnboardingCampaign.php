<?php

namespace App\Messaging\Campaigns;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Core\Enums\NotificationCategory;
use App\Messaging\Entities\CampaignSchedule;
use App\Messaging\Mail\CreateOrgOnboarding;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Builder;

class CreateOrgOnboardingCampaign extends DripCampaign
{
    public function key(): string
    {
        return 'drip.create-org.v1';
    }

    public function audience(Builder $query): Builder
    {
        return $query->doesntHave('organizations');
    }

    public function mailable(User|MailingListEmail $user): Mailable
    {
        return new CreateOrgOnboarding($user);
    }

    public function schedule(): CampaignSchedule
    {
        return new CampaignSchedule;
    }

    public function categories(): array
    {
        return [NotificationCategory::PRODUCT_TIPS];
    }
}
