<?php

namespace App\Environment\Events;

use App\Environment\Models\Environment;

class EnvironmentUpdated extends EnvironmentEvent
{
    public function __construct(public Environment $environment)
    {
        if ($environment->wasChanged('name')) {
            $this->dispatchNameChangedEvent();
        }
    }

    protected function dispatchNameChangedEvent(): void
    {
        EnvironmentNameChanged::dispatch(
            $this->environment,
            $this->environment->getOriginal('name'),
            $this->environment->name
        );
    }
}
