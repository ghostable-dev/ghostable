<?php

namespace App\Messaging\Campaigns\Drip\Series;

use App\Account\Models\User;
use Closure;

class SeriesStep
{
    /**
     * @param  class-string  $primary  First campaign class for this step.
     * @param  array<class-string>  $reminders  Optional reminder campaign classes.
     * @param  int  $cooldownDays  Min days between touches in this step.
     * @param  callable(User): bool  $isComplete  Closure that returns true when this step is "done".
     */
    public function __construct(
        public string $primary,
        public array $reminders,
        public int $cooldownDays,
        public Closure $isComplete,
    ) {}
}
