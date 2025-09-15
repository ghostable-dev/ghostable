<?php

namespace App\Account\Builders;

use App\Account\Concerns\HasNotificationsScopes;
use App\Account\Enums\MailingListEmailSource;
use Illuminate\Database\Eloquent\Builder;

class MailingListEmailBuilder extends Builder
{
    use HasNotificationsScopes;

    public function fromBlog(): Builder
    {
        return $this->fromSource(MailingListEmailSource::BLOG);
    }

    public function fromSource(MailingListEmailSource $source): Builder
    {
        return $this->where('source', $source->value);
    }
}
