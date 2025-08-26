<?php

namespace App\Integration\Integrations\Drata;

use App\Integration\Integrations\Drata\Jobs\SendAuditEvent;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class DrataServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Activity::created(function (Activity $activity) {
            //SendAuditEvent::dispatch($activity->id);
        });
    }
}
