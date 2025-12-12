<?php

namespace App\Integration\Integrations\Vanta\Jobs;

use App\Core\Models\Activity;
use App\Environment\Models\Environment;
use App\Integration\Entities\VantaSettings;
use App\Integration\Integrations\Vanta\VantaClient;
use App\Integration\Models\Integration;
use App\Integration\Support\IntegrationKey;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAuditEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $activityId) {}

    public function handle(VantaClient $client): void
    {
        $activity = Activity::find($this->activityId);

        if (! $activity) {
            return;
        }

        $integration = $this->resolveIntegration($activity);

        if (! $integration) {
            return;
        }

        $token = $integration->secure_settings['access_token'] ?? null;
        $settings = $integration->settings instanceof VantaSettings
            ? $integration->settings
            : VantaSettings::defaults();

        if (! $token) {
            return;
        }

        $client->sendActivity($activity, $token, $settings->base_url ?? null);
    }

    protected function resolveIntegration(Activity $activity): ?Integration
    {
        $organizationId = $this->resolveOrganizationId($activity);

        if (! $organizationId) {
            return null;
        }

        return Integration::query()
            ->where('organization_id', $organizationId)
            ->where('key', IntegrationKey::VANTA)
            ->first();
    }

    protected function resolveOrganizationId(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject instanceof Organization) {
            return $subject->id;
        }

        if ($subject instanceof Project) {
            return $subject->organization_id;
        }

        if ($subject instanceof Environment) {
            return optional($subject->project)->organization_id
                ?? $subject->project()->value('organization_id');
        }

        return (string) (data_get($activity->properties, 'organization_id')
            ?? data_get($activity->properties, 'organization.id')
            ?? data_get($activity->properties, 'organizationId')) ?: null;
    }
}
