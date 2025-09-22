<?php

namespace App\Messaging\Campaigns\Drip;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Core\Enums\NotificationCategory;
use App\Messaging\Mail\Drip\OrganizationSetupNudgeMailable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Builder;

class OrganizationSetupNudge extends DripCampaign
{
    public function key(): string
    {
        return 'drip.organization-setup.v1';
    }

    public function audience(Builder $query): Builder
    {
        return $query->doesntHave('organizations');
    }

    public function mailable(User|MailingListEmail $user): Mailable
    {
        return new OrganizationSetupNudgeMailable($user);
    }

    public function categories(): array
    {
        return [NotificationCategory::PRODUCT_TIPS];
    }
}
