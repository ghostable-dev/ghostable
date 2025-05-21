<?php

namespace App\Environment\Entities;

use App\Environment\Enums\PushResultStatus;
use Spatie\LaravelData\Data;

class PushResultData extends Data
{
    public function __construct(
        public int $added = 0,
        public int $updated = 0,
        public int $removed = 0,
    )
    {}
    
    public function status(): PushResultStatus
    {
        return ($this->added + $this->updated + $this->removed) === 0
            ? PushResultStatus::UNCHANGED
            : PushResultStatus::UPDATED;
    }
}