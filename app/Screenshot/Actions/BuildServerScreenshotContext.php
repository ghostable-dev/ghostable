<?php

declare(strict_types=1);

namespace App\Screenshot\Actions;

use App\Environment\Models\Environment;
use App\Integration\Models\IntegrationClient;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use RuntimeException;

final class BuildServerScreenshotContext
{
    private const ORGANIZATION_ALIAS = 'organization.northstar';

    private const ORGANIZATION_SLUG = 'northstar-labs';

    /** @var array<string, string> */
    private const PROJECT_ALIASES = [
        'project.control-plane' => 'Control Plane',
        'project.customer-api' => 'Customer API',
        'project.marketing-site' => 'Marketing Site',
    ];

    /** @var array<string, array{project: string, environment: string}> */
    private const ENVIRONMENT_ALIASES = [
        'environment.control-plane.production' => ['project' => 'Control Plane', 'environment' => 'production'],
        'environment.control-plane.staging' => ['project' => 'Control Plane', 'environment' => 'staging'],
        'environment.control-plane.qa' => ['project' => 'Control Plane', 'environment' => 'qa'],
        'environment.customer-api.production' => ['project' => 'Customer API', 'environment' => 'production'],
        'environment.customer-api.staging' => ['project' => 'Customer API', 'environment' => 'staging'],
        'environment.marketing-site.production' => ['project' => 'Marketing Site', 'environment' => 'production'],
        'environment.marketing-site.preview' => ['project' => 'Marketing Site', 'environment' => 'preview'],
    ];

    /** @var array<string, string> */
    private const INTEGRATION_CLIENT_ALIASES = [
        'integration_client.northstar-compliance-gateway' => 'northstar-compliance-gateway',
    ];

    public function handle(): array
    {
        $organization = Organization::query()
            ->where('slug', self::ORGANIZATION_SLUG)
            ->first();

        if (! $organization) {
            throw new RuntimeException('Northstar Labs screenshot data was not found. Run `php artisan app:seed-screenshot-account --force` first.');
        }

        $projects = Project::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy('name');

        $environments = Environment::query()
            ->with('project')
            ->whereIn('project_id', $projects->pluck('id'))
            ->get()
            ->keyBy(static fn (Environment $environment): string => $environment->project->name.'::'.$environment->name);

        $integrationClients = IntegrationClient::query()
            ->where('owner_organization_id', $organization->id)
            ->get()
            ->keyBy('key');

        $aliases = [
            self::ORGANIZATION_ALIAS => $this->organizationAlias($organization),
        ];

        foreach (self::PROJECT_ALIASES as $alias => $name) {
            $project = $projects->get($name);

            if (! $project) {
                throw new RuntimeException(sprintf('Screenshot project "%s" is missing from the seeded account.', $name));
            }

            $aliases[$alias] = $this->projectAlias($project);
        }

        foreach (self::ENVIRONMENT_ALIASES as $alias => $definition) {
            $environment = $environments->get($definition['project'].'::'.$definition['environment']);

            if (! $environment) {
                throw new RuntimeException(sprintf(
                    'Screenshot environment "%s / %s" is missing from the seeded account.',
                    $definition['project'],
                    $definition['environment'],
                ));
            }

            $aliases[$alias] = $this->environmentAlias($environment);
        }

        foreach (self::INTEGRATION_CLIENT_ALIASES as $alias => $key) {
            $client = $integrationClients->get($key);

            if (! $client) {
                throw new RuntimeException(sprintf('Screenshot integration client "%s" is missing from the seeded account.', $key));
            }

            $aliases[$alias] = $this->integrationClientAlias($client);
        }

        ksort($aliases);

        return [
            'version' => 1,
            'platform' => 'server',
            'base_url' => rtrim((string) config('app.url'), '/'),
            'output_root' => 'storage/app/screenshots/server',
            'aliases' => $aliases,
            'route_templates' => $this->routeTemplates(),
        ];
    }

    /**
     * @return array<string, array{uri: string, parameters: array<int, string>}>
     */
    private function routeTemplates(): array
    {
        $templates = RouteFacade::getRoutes()
            ->getRoutesByName();

        $filtered = collect($templates)
            ->filter(static fn (Route $route, string $name): bool => str_starts_with($name, 'dashboard')
                || str_starts_with($name, 'organization.settings.')
                || str_starts_with($name, 'project.')
                || str_starts_with($name, 'environment.'))
            ->map(static fn (Route $route): array => [
                'uri' => '/'.ltrim($route->uri(), '/'),
                'parameters' => $route->parameterNames(),
            ])
            ->all();

        ksort($filtered);

        return $filtered;
    }

    /**
     * @return array<string, mixed>
     */
    private function organizationAlias(Organization $organization): array
    {
        return [
            'type' => 'organization',
            'id' => (string) $organization->getKey(),
            'route_key' => (string) $organization->getRouteKey(),
            'name' => $organization->name,
            'slug' => $organization->slug,
            'urls' => [
                'general' => route('organization.settings.general', absolute: false),
                'members' => route('organization.settings.members', absolute: false),
                'notifications' => route('organization.settings.notifications', absolute: false),
                'billing' => route('organization.settings.billing', absolute: false),
                'integrations' => route('organization.settings.integrations', absolute: false),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function projectAlias(Project $project): array
    {
        return [
            'type' => 'project',
            'id' => (string) $project->getKey(),
            'route_key' => (string) $project->getRouteKey(),
            'name' => $project->name,
            'organization_id' => (string) $project->organization_id,
            'urls' => [
                'environments' => route('project.environments', $project, absolute: false),
                'activity' => route('project.activity', $project, absolute: false),
                'settings_general' => route('project.settings.general', $project, absolute: false),
                'settings_access' => route('project.settings.access', $project, absolute: false),
                'settings_notifications' => route('project.settings.notifications', $project, absolute: false),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function environmentAlias(Environment $environment): array
    {
        return [
            'type' => 'environment',
            'id' => (string) $environment->getKey(),
            'route_key' => (string) $environment->getRouteKey(),
            'name' => $environment->name,
            'project_id' => (string) $environment->project_id,
            'project_name' => $environment->project->name,
            'urls' => [
                'variables' => route('environment.variables', $environment, absolute: false),
                'variables_zero' => route('environment.variables.zero', $environment, absolute: false),
                'activity' => route('environment.activity', $environment, absolute: false),
                'settings_general' => route('environment.settings.general', $environment, absolute: false),
                'settings_access' => route('environment.settings.access', $environment, absolute: false),
                'settings_notifications' => route('environment.settings.notifications', $environment, absolute: false),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function integrationClientAlias(IntegrationClient $client): array
    {
        return [
            'type' => 'integration_client',
            'id' => (string) $client->getKey(),
            'route_key' => (string) $client->getRouteKey(),
            'name' => $client->name,
            'key' => $client->key,
            'client_id' => $client->client_id,
            'urls' => [
                'edit' => route('organization.settings.integrations.edit', ['client' => $client], absolute: false),
            ],
        ];
    }
}
