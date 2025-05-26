<?php

namespace App\Team\Builders;

use Illuminate\Database\Eloquent\Builder;

class TeamBuilder extends Builder
{
    public function personal(): Builder
    {
        return $this->where('is_personal', true);
    }
}