<?php

declare(strict_types=1);

namespace App\Support;

use App\Environment\Models\Environment;
use App\Project\Models\Project;

class DesktopDeepLink
{
    public static function forEnvironment(
        Environment $environment,
        ?string $variableName = null,
        ?string $detailPanel = null,
        ?string $sourceEnvironmentId = null,
        ?string $sourceEnvironmentName = null,
        ?string $promotionRequestId = null
    ): ?string {
        $environment->loadMissing('project.organization');

        $project = $environment->project;
        $organization = $project?->organization;

        if (! $project || ! $organization) {
            return null;
        }

        return self::build(
            host: 'environment',
            pathSegments: [
                (string) $organization->getKey(),
                (string) $project->getKey(),
                (string) $environment->getKey(),
            ],
            query: [
                'org_name' => $organization->name,
                'project_name' => $project->name,
                'environment_name' => $environment->name,
                'variable' => self::normalized($variableName),
                'panel' => self::normalized($detailPanel),
                'source_environment_id' => self::normalized($sourceEnvironmentId),
                'source_environment_name' => self::normalized($sourceEnvironmentName),
                'promotion_request_id' => self::normalized($promotionRequestId),
            ],
        );
    }

    public static function forProject(Project $project, ?string $section = null): ?string
    {
        $project->loadMissing('organization');

        if (! $project->organization) {
            return null;
        }

        return self::build(
            host: 'project',
            pathSegments: [
                (string) $project->organization->getKey(),
                (string) $project->getKey(),
            ],
            query: [
                'org_name' => $project->organization->name,
                'project_name' => $project->name,
                'section' => self::normalized($section),
            ],
        );
    }

    public static function forOrganizationKeyReshare(string $organizationId, string $requestId): string
    {
        return self::build(
            host: 'organization',
            pathSegments: [$organizationId, 'key-reshare', $requestId],
        );
    }

    /**
     * @param  array<int, string>  $pathSegments
     * @param  array<string, ?string>  $query
     */
    private static function build(string $host, array $pathSegments, array $query = []): string
    {
        $path = sprintf(
            '%s://%s/%s',
            self::scheme(),
            $host,
            implode('/', array_map(static fn (string $segment): string => rawurlencode($segment), $pathSegments)),
        );

        $query = array_filter($query, static fn (?string $value): bool => $value !== null && $value !== '');
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $queryString !== '' ? $path.'?'.$queryString : $path;
    }

    private static function scheme(): string
    {
        return app()->isProduction() ? 'ghostable' : 'ghostable-local';
    }

    private static function normalized(?string $value): ?string
    {
        $value = $value !== null
            ? trim($value)
            : null;

        return $value !== '' ? $value : null;
    }
}
