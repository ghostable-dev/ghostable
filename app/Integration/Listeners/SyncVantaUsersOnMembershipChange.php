<?php

declare(strict_types=1);

namespace App\Integration\Listeners;

use App\Integration\Integrations\Vanta\Actions\SyncUsersAction;
use App\Integration\Models\Integration;
use App\Integration\Support\IntegrationKey;
use App\Organization\Events\MemberJoined;
use App\Organization\Events\MemberRemoved;
use App\Organization\Events\MemberRoleChanged;
use Illuminate\Support\Facades\Log;

class SyncVantaUsersOnMembershipChange
{
    public function handle(MemberJoined|MemberRemoved|MemberRoleChanged $event): void
    {
        $organization = $event->organization;

        if (! $organization || ! $organization->hasPaidPlan()) {
            return;
        }

        $integration = Integration::query()
            ->where('organization_id', $organization->id)
            ->where('key', IntegrationKey::VANTA)
            ->first();

        if (! $integration) {
            return;
        }

        try {
            app(SyncUsersAction::class)->handleForIntegration($integration, strict: false, requirePaidPlan: true);
        } catch (\Throwable $e) {
            Log::warning('Vanta sync failed on membership change', [
                'organization_id' => $organization->id,
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
