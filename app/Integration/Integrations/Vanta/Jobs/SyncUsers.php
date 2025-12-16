<?php

declare(strict_types=1);

namespace App\Integration\Integrations\Vanta\Jobs;

use App\Integration\Enums\IntegrationStatus;
use App\Integration\Integrations\Vanta\Actions\SyncUsersAction;
use App\Integration\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $integrationId = null,
        public bool $requirePaidPlan = false
    ) {}

    public function handle(SyncUsersAction $syncUsers): void
    {
        if ($this->integrationId) {
            $integration = Integration::find($this->integrationId);

            if ($integration && $integration->status === IntegrationStatus::Active) {
                $syncUsers->handleForIntegration(
                    integration: $integration,
                    strict: false,
                    requirePaidPlan: $this->requirePaidPlan
                );
            }

            return;
        }

        $syncUsers->handleForActiveIntegrations(
            requirePaidPlan: $this->requirePaidPlan
        );
    }
}
