<?php

namespace App\Messaging\Contracts;

use App\Account\Models\User;
use App\Messaging\Enums\CampaignType;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Builder;

interface Campaign
{
    public function key(): string;

    public function kind(): CampaignType;

    public function audience(Builder $query): Builder;

    public function eligible(User $user): bool;

    public function mailable(User $user): Mailable;
}
