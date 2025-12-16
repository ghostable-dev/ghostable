<?php

declare(strict_types=1);

namespace App\Integration\Integrations\Vanta\Actions;

use App\Account\Models\User;
use App\Integration\Entities\VantaSettings;
use App\Integration\Enums\IntegrationStatus;
use App\Integration\Integrations\Vanta\Support\PermissionLevelResolver;
use App\Integration\Integrations\Vanta\Support\UserResourceMapper;
use App\Integration\Integrations\Vanta\VantaClient;
use App\Integration\Models\Integration;
use App\Integration\Support\IntegrationKey;
use RuntimeException;

class SyncUsersAction
{
    public function __construct(
        protected VantaClient $client,
        protected PermissionLevelResolver $permissionResolver,
        protected UserResourceMapper $resourceMapper,
    ) {}

    public function handleForActiveIntegrations(bool $strict = false, bool $requirePaidPlan = false): void
    {
        Integration::query()
            ->where('key', IntegrationKey::VANTA)
            ->where('status', IntegrationStatus::Active)
            ->with([
                'organization.users',
                'organization.projects.permissionOverrides',
                'organization.projects.environments.permissionOverrides',
            ])
            ->get()
            ->each(fn (Integration $integration) => $this->handleForIntegration($integration, $strict, $requirePaidPlan));
    }

    public function handleForIntegration(Integration $integration, bool $strict = false, bool $requirePaidPlan = false): void
    {
        if ($integration->key !== IntegrationKey::VANTA) {
            return;
        }

        if ($integration->status !== IntegrationStatus::Active) {
            if ($strict) {
                throw new RuntimeException('Vanta integration is not active.');
            }

            return;
        }

        $integration->loadMissing([
            'organization.users',
            'organization.projects.permissionOverrides',
            'organization.projects.environments.permissionOverrides',
        ]);

        $organization = $integration->organization;
        $settings = $integration->settings instanceof VantaSettings
            ? $integration->settings
            : VantaSettings::defaults();

        $token = $integration->secure_settings['access_token'] ?? null;
        $resourceId = config('vanta.resource_id') ?: $settings->resource_id;
        $baseUrl = $settings->base_url ?? null;

        if (! $this->guardConfiguration($organization !== null, 'Vanta integration is missing an organization.', $strict)) {
            return;
        }

        if (! $this->guardConfiguration($settings->sync_users_enabled, 'Vanta user sync is disabled.', $strict)) {
            return;
        }

        if (! $this->guardConfiguration(! empty($token), 'Vanta access token is missing.', $strict)) {
            return;
        }

        if (! $this->guardConfiguration(! empty($resourceId), 'Vanta resource ID is missing from configuration.', $strict)) {
            return;
        }

        if ($requirePaidPlan && (! $organization->plan || $organization->plan->isFree())) {
            return;
        }

        $resources = $organization->users
            ->map(fn (User $user) => $this->resourceMapper->map($user, $organization, $this->permissionResolver))
            ->filter(fn (array $resource) => isset($resource['email']))
            ->values()
            ->all();

        if (empty($resources)) {
            if ($strict) {
                throw new RuntimeException('No users available to sync to Vanta.');
            }

            return;
        }

        $this->client->sendResources($resourceId, $resources, $token, $baseUrl);
    }

    protected function guardConfiguration(bool $condition, string $message, bool $strict): bool
    {
        if ($condition) {
            return true;
        }

        if ($strict) {
            throw new RuntimeException($message);
        }

        return false;
    }
}
