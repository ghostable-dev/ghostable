<?php

namespace App\Integration\Integrations\Vanta;

use App\Core\Models\Activity;
use Illuminate\Support\Facades\Http;

class VantaClient
{
    public function sendActivity(Activity $activity): void
    {
        $token = config('vanta.access_token');
        $url = rtrim(config('vanta.base_url'), '/').'/v1/connectors/events';

        if (! $token) {
            return;
        }

        Http::withToken($token)
            ->post($url, [
                'timestamp' => optional($activity->created_at)->toIso8601String(),
                'action' => $activity->event,
                'actor' => [
                    'id' => $activity->causer_id,
                    'type' => $activity->causer_type,
                ],
                'target' => [
                    'id' => $activity->subject_id,
                    'type' => $activity->subject_type,
                ],
                'metadata' => $activity->properties ?? [],
                'description' => $activity->description,
            ]);
    }
}
