<?php

declare(strict_types=1);

namespace App\Organization;

use App\Environment\Models\Environment;
use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Jobs\DeliverAuditWebhookActivity;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhook;
use App\Project\Models\Project;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

final class OrganizationAuditWebhookServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Activity::created(function (Activity $activity): void {
            if (request()->headers->get('X-Ghostable-Screenshot') === '1') {
                return;
            }

            $organizationId = $this->resolveOrganizationId($activity);

            if (! $organizationId) {
                return;
            }

            OrganizationAuditWebhook::query()
                ->where('organization_id', $organizationId)
                ->where('status', OrganizationAuditWebhookStatus::ACTIVE)
                ->pluck('id')
                ->each(
                    static fn ($webhookId): mixed => DeliverAuditWebhookActivity::dispatch(
                        webhookId: (string) $webhookId,
                        activityId: (int) $activity->id,
                    )
                );
        });
    }

    private function resolveOrganizationId(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject instanceof Organization) {
            return (string) $subject->id;
        }

        if ($subject instanceof Project) {
            return (string) $subject->organization_id;
        }

        if ($subject instanceof Environment) {
            return (string) (
                optional($subject->project)->organization_id
                ?? $subject->project()->value('organization_id')
            );
        }

        $fromProperties = data_get($activity->properties, 'organization_id')
            ?? data_get($activity->properties, 'organization.id')
            ?? data_get($activity->properties, 'organizationId');

        if (! is_string($fromProperties) || $fromProperties === '') {
            return null;
        }

        return $fromProperties;
    }
}
