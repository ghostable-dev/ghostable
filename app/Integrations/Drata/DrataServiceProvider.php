<?php

namespace App\Integrations\Drata;

use Spatie\Activitylog\Models\Activity;
use App\Integrations\Drata\Jobs\SendAuditEvent;
use Illuminate\Support\ServiceProvider;

class DrataServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Activity::created(function (Activity $activity) {
            SendAuditEvent::dispatch($activity->id);
        });
    }
}
