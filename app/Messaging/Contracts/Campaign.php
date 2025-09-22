<?php

namespace App\Messaging\Contracts;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Messaging\Entities\CampaignSchedule;
use App\Messaging\Enums\CampaignType;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Builder;

interface Campaign
{
    public function key(): string;

    public function kind(): CampaignType;

    public function schedule(): CampaignSchedule;

    public function audience(Builder $query): Builder;

    public function eligible(User|MailingListEmail $user): bool;

    public function mailable(User|MailingListEmail $user): Mailable;

    public function categories(): array;
}
