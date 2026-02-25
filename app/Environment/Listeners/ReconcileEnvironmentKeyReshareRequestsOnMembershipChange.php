<?php

declare(strict_types=1);

namespace App\Environment\Listeners;

use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use App\Organization\Events\MemberRemoved;
use App\Organization\Events\MemberRoleChanged;

final class ReconcileEnvironmentKeyReshareRequestsOnMembershipChange
{
    public function __construct(
        private readonly ManageEnvironmentKeyReshareRequests $manageEnvironmentKeyReshareRequests,
    ) {}

    public function handle(MemberRemoved|MemberRoleChanged $event): void
    {
        if (! $this->manageEnvironmentKeyReshareRequests->isEnabledForOrganization($event->organization)) {
            return;
        }

        if ($event instanceof MemberRemoved) {
            $this->manageEnvironmentKeyReshareRequests->cancelForUser(
                organization: $event->organization,
                targetUser: $event->user,
                reason: 'membership_revoked',
                actor: null,
                request: null,
                triggerSource: 'reconcile',
            );

            return;
        }

        $this->manageEnvironmentKeyReshareRequests->syncForOrganization(
            organization: $event->organization,
            triggerSource: 'reconcile',
            actor: null,
            request: null,
            notifyActors: true,
        );
    }
}
