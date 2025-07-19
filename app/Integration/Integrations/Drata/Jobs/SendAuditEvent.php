<?php

namespace App\Integration\Integrations\Drata\Jobs;

use App\Core\Models\Activity;
use App\Integration\Integrations\Drata\DrataClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAuditEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $activityId) {}

    public function handle(DrataClient $client): void
    {
        $activity = Activity::find($this->activityId);

        if ($activity) {
            $client->sendActivity($activity);
        }
    }
}
