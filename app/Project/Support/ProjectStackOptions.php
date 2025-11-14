<?php

namespace App\Project\Support;

use App\Project\Entities\ProjectStackData;
use App\Project\Enums\DeploymentProvider;
use App\Project\Enums\ProjectStackTag;

class ProjectStackOptions
{
    private const LANGUAGE_FRAMEWORK_MAP = [
        'php' => [
            ProjectStackTag::FrameworkLaravel,
            ProjectStackTag::FrameworkSymfony,
            ProjectStackTag::FrameworkYii2,
            ProjectStackTag::FrameworkCakePHP,
            ProjectStackTag::FrameworkCodeIgniter,
            ProjectStackTag::FrameworkPlainPHP,
            ProjectStackTag::FrameworkOther,
        ],
        'javascript_typescript' => [
            ProjectStackTag::FrameworkNode,
            ProjectStackTag::FrameworkNext,
            ProjectStackTag::FrameworkRemix,
            ProjectStackTag::FrameworkNuxt,
            ProjectStackTag::FrameworkSvelteKit,
            ProjectStackTag::FrameworkAstro,
            ProjectStackTag::FrameworkSolidStart,
            ProjectStackTag::FrameworkVanillaJS,
            ProjectStackTag::FrameworkOther,
        ],
        'python' => [
            ProjectStackTag::FrameworkDjango,
            ProjectStackTag::FrameworkFastAPI,
            ProjectStackTag::FrameworkFlask,
            ProjectStackTag::FrameworkPlainPython,
            ProjectStackTag::FrameworkOther,
        ],
        'ruby' => [
            ProjectStackTag::FrameworkRails,
            ProjectStackTag::FrameworkSinatra,
            ProjectStackTag::FrameworkPlainRuby,
            ProjectStackTag::FrameworkOther,
        ],
        'language_other' => [
            ProjectStackTag::FrameworkOther,
        ],
    ];

    private const LANGUAGE_PLATFORM_MAP = [
        'php' => [
            ProjectStackTag::PlatformLaravelCloud,
            ProjectStackTag::PlatformLaravelForge,
            ProjectStackTag::PlatformLaravelVapor,
            ProjectStackTag::PlatformDockerCompose,
            ProjectStackTag::PlatformKubernetes,
            ProjectStackTag::PlatformAWS,
            ProjectStackTag::PlatformDigitalOcean,
            ProjectStackTag::PlatformRender,
            ProjectStackTag::PlatformFlyIO,
            ProjectStackTag::PlatformSelfHosted,
        ],
        'javascript_typescript' => [
            ProjectStackTag::PlatformVercel,
            ProjectStackTag::PlatformNetlify,
            ProjectStackTag::PlatformRender,
            ProjectStackTag::PlatformFlyIO,
            ProjectStackTag::PlatformAWS,
            ProjectStackTag::PlatformDigitalOcean,
            ProjectStackTag::PlatformDockerCompose,
            ProjectStackTag::PlatformKubernetes,
            ProjectStackTag::PlatformSelfHosted,
        ],
        'python' => [
            ProjectStackTag::PlatformAWS,
            ProjectStackTag::PlatformDigitalOcean,
            ProjectStackTag::PlatformRender,
            ProjectStackTag::PlatformFlyIO,
            ProjectStackTag::PlatformDockerCompose,
            ProjectStackTag::PlatformKubernetes,
            ProjectStackTag::PlatformSelfHosted,
        ],
        'ruby' => [
            ProjectStackTag::PlatformRender,
            ProjectStackTag::PlatformFlyIO,
            ProjectStackTag::PlatformAWS,
            ProjectStackTag::PlatformDigitalOcean,
            ProjectStackTag::PlatformDockerCompose,
            ProjectStackTag::PlatformKubernetes,
            ProjectStackTag::PlatformSelfHosted,
        ],
        'language_other' => [
            ProjectStackTag::PlatformDockerCompose,
            ProjectStackTag::PlatformKubernetes,
            ProjectStackTag::PlatformAWS,
            ProjectStackTag::PlatformDigitalOcean,
            ProjectStackTag::PlatformVercel,
            ProjectStackTag::PlatformNetlify,
            ProjectStackTag::PlatformRender,
            ProjectStackTag::PlatformFlyIO,
            ProjectStackTag::PlatformSelfHosted,
        ],
    ];

    public static function languageOptions(): array
    {
        return ProjectStackTag::languageTags();
    }

    public static function frameworksFor(?string $language): array
    {
        if ($language === null) {
            return [];
        }

        return self::LANGUAGE_FRAMEWORK_MAP[$language] ?? [ProjectStackTag::FrameworkOther];
    }

    public static function platformsFor(?string $language): array
    {
        if ($language === null) {
            return [];
        }

        return self::LANGUAGE_PLATFORM_MAP[$language] ?? ProjectStackTag::platformTags();
    }

    public static function providerForPlatform(?string $platform): DeploymentProvider
    {
        return match ($platform) {
            ProjectStackTag::PlatformLaravelCloud->value => DeploymentProvider::LARAVEL_CLOUD,
            ProjectStackTag::PlatformLaravelForge->value => DeploymentProvider::LARAVEL_FORGE,
            ProjectStackTag::PlatformLaravelVapor->value => DeploymentProvider::LARAVEL_VAPOR,
            default => DeploymentProvider::OTHER,
        };
    }

    public static function stackData(
        ?string $language,
        ?string $framework,
        ?string $platform
    ): ?ProjectStackData {
        if (! $language && ! $framework && ! $platform) {
            return null;
        }

        return new ProjectStackData(
            language: $language ? ProjectStackTag::from($language) : null,
            framework: $framework ? ProjectStackTag::from($framework) : null,
            platform: $platform ? ProjectStackTag::from($platform) : null,
        );
    }
}
