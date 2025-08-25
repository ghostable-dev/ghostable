<?php

namespace App\Organization\Builders;

use Illuminate\Database\Eloquent\Builder;

class OrganizationBuilder extends Builder
{
    public function personal(): Builder
    {
        return $this->where('is_personal', true);
    }
}
