<?php

namespace App\Integration\Integrations\Vanta;

use App\Core\Models\Activity;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VantaClient
{
    public function sendActivity(Activity $activity, string $token, ?string $baseUrl = null): void
    {
        $baseUrl ??= config('vanta.base_url');

        if (! $token) {
            return;
        }

        $url = $this->buildUrl($baseUrl, 'connectors/v1/events');

        try {
            $response = Http::withToken($token)->post($url, [
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

            if ($response->failed()) {
                Log::error('Vanta activity send failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'url' => $url,
                    'activity_id' => $activity->id,
                ]);

                $response->throw();
            }
        } catch (RequestException $e) {
            Log::error('Vanta activity send exception', [
                'message' => $e->getMessage(),
                'url' => $url,
                'activity_id' => $activity->id,
            ]);

            throw $e;
        }
    }

    public function sendResources(string $resourceId, array $resources, string $token, ?string $baseUrl = null): void
    {
        if (! $token || $resourceId === '' || empty($resources)) {
            return;
        }

        $url = $this->buildUrl($baseUrl, 'v1/resources/user_account');

        $payload = [
            'resourceId' => $resourceId,
            'resources' => array_values($resources),
        ];

        try {
            $response = Http::withToken($token)->put($url, $payload);

            if ($response->failed()) {
                Log::error('Vanta resource sync failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'url' => $url,
                    'resource_id' => $resourceId,
                    'resource_count' => count($resources),
                    'payload' => $payload,
                ]);

                $response->throw();
            }
        } catch (RequestException $e) {
            Log::error('Vanta resource sync exception', [
                'message' => $e->getMessage(),
                'url' => $url,
                'resource_id' => $resourceId,
                'resource_count' => count($resources),
            ]);

            throw $e;
        }
    }

    /**
     * Build a Vanta API URL while avoiding duplicate version prefixes when the base URL already includes /v1.
     */
    protected function buildUrl(?string $baseUrl, string $path): string
    {
        $base = rtrim((string) ($baseUrl ?? config('vanta.base_url')), '/');
        $path = ltrim($path, '/');

        if (preg_match('~/v\\d+$~', $base)) {
            $base = rtrim(preg_replace('~/v\\d+$~', '', $base), '/');
        }

        return $base.'/'.$path;
    }
}
