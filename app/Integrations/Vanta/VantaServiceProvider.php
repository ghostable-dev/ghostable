<?php

namespace App\Integrations\Vanta;

use App\Integrations\Vanta\Jobs\SendAuditEvent;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class VantaServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Activity::created(function (Activity $activity) {
            SendAuditEvent::dispatch($activity->id);
        });
    }
}
