<?php

declare(strict_types=1);

namespace App\Environment\Console\Commands;

use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Models\Organization;
use Illuminate\Console\Command;

final class ReconcileEnvironmentKeyReshareRequestsCommand extends Command
{
    protected $signature = 'environment:key-reshare:reconcile
        {--organization= : Restrict reconciliation to a specific organization UUID}
        {--pending-only : Reconcile existing pending requests only (skip full device/environment scan)}
        {--no-notify : Do not send actor notifications for newly created pending requests}';

    protected $description = 'Reconcile pending environment key re-share requests for API v2 guided key sharing.';

    public function handle(ManageEnvironmentKeyReshareRequests $manageEnvironmentKeyReshareRequests): int
    {
        $organizationId = $this->option('organization');
        $pendingOnly = (bool) $this->option('pending-only');
        $notifyActors = ! (bool) $this->option('no-notify');

        $organizationsQuery = Organization::query()
            ->when(
                is_string($organizationId) && $organizationId !== '',
                fn ($query) => $query->whereKey((string) $organizationId)
            )
            ->orderBy('name');

        if ($pendingOnly && (! is_string($organizationId) || $organizationId === '')) {
            $organizationIds = EnvironmentKeyReshareRequest::query()
                ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
                ->distinct()
                ->pluck('organization_id');

            if ($organizationIds->isEmpty()) {
                $this->line('No pending key re-share requests found. Nothing to reconcile.');

                return self::SUCCESS;
            }

            $organizationsQuery->whereIn('id', $organizationIds->all());
        }

        $organizations = $organizationsQuery->get();

        if ($organizations->isEmpty()) {
            $this->warn('No organizations matched the reconciliation scope.');

            return self::SUCCESS;
        }

        $resolvedTotal = 0;
        $createdTotal = 0;

        foreach ($organizations as $organization) {
            if (! $manageEnvironmentKeyReshareRequests->isEnabledForOrganization($organization)) {
                $this->line(sprintf('Skipping %s (%s): guided_key_reshare_v2 disabled.', $organization->name, (string) $organization->getKey()));

                continue;
            }

            if ($pendingOnly) {
                $resolvedCount = $manageEnvironmentKeyReshareRequests->reconcilePendingForOrganization(
                    organization: $organization,
                    triggerSource: 'reconcile',
                    actor: null,
                    request: null,
                );

                $resolvedTotal += $resolvedCount;

                $this->info(sprintf(
                    'Reconciled %s (%s): %d pending request%s updated.',
                    $organization->name,
                    (string) $organization->getKey(),
                    $resolvedCount,
                    $resolvedCount === 1 ? '' : 's'
                ));

                continue;
            }

            $created = $manageEnvironmentKeyReshareRequests->syncForOrganization(
                organization: $organization,
                triggerSource: 'reconcile',
                actor: null,
                request: null,
                notifyActors: $notifyActors,
            );

            $createdCount = $created->count();
            $createdTotal += $createdCount;

            $this->info(sprintf(
                'Reconciled %s (%s): %d pending request%s created.',
                $organization->name,
                (string) $organization->getKey(),
                $createdCount,
                $createdCount === 1 ? '' : 's'
            ));
        }

        $this->newLine();

        if ($pendingOnly) {
            $this->info(sprintf('Done. %d pending request%s updated.', $resolvedTotal, $resolvedTotal === 1 ? '' : 's'));

            return self::SUCCESS;
        }

        $this->info(sprintf('Done. %d pending request%s created.', $createdTotal, $createdTotal === 1 ? '' : 's'));

        return self::SUCCESS;
    }
}
