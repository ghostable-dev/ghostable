<?php

namespace App\Project\Enums;

enum ProjectStackTag: string
{
    // Languages
    case LanguagePHP = 'php';
    case LanguageJavaScript = 'javascript_typescript';
    case LanguagePython = 'python';
    case LanguageRuby = 'ruby';
    case LanguageOther = 'language_other';

    // Frameworks
    case FrameworkLaravel = 'laravel';
    case FrameworkSymfony = 'symfony';
    case FrameworkYii2 = 'yii2';
    case FrameworkCakePHP = 'cakephp';
    case FrameworkCodeIgniter = 'codeigniter';
    case FrameworkPlainPHP = 'plain_php';
    case FrameworkNode = 'node_express';
    case FrameworkNext = 'nextjs';
    case FrameworkRemix = 'remix';
    case FrameworkNuxt = 'nuxtjs';
    case FrameworkSvelteKit = 'sveltekit';
    case FrameworkAstro = 'astro';
    case FrameworkSolidStart = 'solidstart';
    case FrameworkVanillaJS = 'vanilla_js';
    case FrameworkDjango = 'django';
    case FrameworkFastAPI = 'fastapi';
    case FrameworkFlask = 'flask';
    case FrameworkPlainPython = 'plain_python';
    case FrameworkRails = 'rails';
    case FrameworkSinatra = 'sinatra';
    case FrameworkPlainRuby = 'plain_ruby';
    case FrameworkOther = 'framework_other';

    // Platforms
    case PlatformLaravelCloud = 'laravel_cloud';
    case PlatformLaravelForge = 'laravel_forge';
    case PlatformLaravelVapor = 'laravel_vapor';
    case PlatformDockerCompose = 'docker_compose';
    case PlatformKubernetes = 'kubernetes';
    case PlatformAWS = 'aws';
    case PlatformDigitalOcean = 'digitalocean';
    case PlatformVercel = 'vercel';
    case PlatformNetlify = 'netlify';
    case PlatformRender = 'render';
    case PlatformFlyIO = 'flyio';
    case PlatformSelfHosted = 'platform_other';

    public function label(): string
    {
        return match ($this) {
            // Languages
            self::LanguagePHP => 'PHP',
            self::LanguageJavaScript => 'JavaScript / TypeScript',
            self::LanguagePython => 'Python',
            self::LanguageRuby => 'Ruby',
            self::LanguageOther => 'Other',

            // Frameworks
            self::FrameworkLaravel => 'Laravel',
            self::FrameworkSymfony => 'Symfony',
            self::FrameworkYii2 => 'Yii 2',
            self::FrameworkCakePHP => 'CakePHP',
            self::FrameworkCodeIgniter => 'CodeIgniter',
            self::FrameworkPlainPHP => 'Plain PHP',
            self::FrameworkNode => 'Node.js / Express',
            self::FrameworkNext => 'Next.js',
            self::FrameworkRemix => 'Remix',
            self::FrameworkNuxt => 'Nuxt',
            self::FrameworkSvelteKit => 'SvelteKit',
            self::FrameworkAstro => 'Astro',
            self::FrameworkSolidStart => 'SolidStart',
            self::FrameworkVanillaJS => 'Vanilla JS',
            self::FrameworkDjango => 'Django',
            self::FrameworkFastAPI => 'FastAPI',
            self::FrameworkFlask => 'Flask',
            self::FrameworkPlainPython => 'Plain Python',
            self::FrameworkRails => 'Ruby on Rails',
            self::FrameworkSinatra => 'Sinatra',
            self::FrameworkPlainRuby => 'Plain Ruby',
            self::FrameworkOther => 'Other',

            // Platforms
            self::PlatformLaravelCloud => 'Laravel Cloud',
            self::PlatformLaravelForge => 'Laravel Forge',
            self::PlatformLaravelVapor => 'Laravel Vapor',
            self::PlatformDockerCompose => 'Docker Compose',
            self::PlatformKubernetes => 'Kubernetes',
            self::PlatformAWS => 'AWS',
            self::PlatformDigitalOcean => 'DigitalOcean',
            self::PlatformVercel => 'Vercel',
            self::PlatformNetlify => 'Netlify',
            self::PlatformRender => 'Render',
            self::PlatformFlyIO => 'Fly.io',
            self::PlatformSelfHosted => 'Self-hosted / Other',
        };
    }

    public function category(): ProjectStackCategory
    {
        return match ($this) {
            self::LanguagePHP,
            self::LanguageJavaScript,
            self::LanguagePython,
            self::LanguageRuby,
            self::LanguageOther => ProjectStackCategory::LANGUAGE,

            self::FrameworkLaravel,
            self::FrameworkSymfony,
            self::FrameworkYii2,
            self::FrameworkCakePHP,
            self::FrameworkCodeIgniter,
            self::FrameworkPlainPHP,
            self::FrameworkNode,
            self::FrameworkNext,
            self::FrameworkRemix,
            self::FrameworkNuxt,
            self::FrameworkSvelteKit,
            self::FrameworkAstro,
            self::FrameworkSolidStart,
            self::FrameworkVanillaJS,
            self::FrameworkDjango,
            self::FrameworkFastAPI,
            self::FrameworkFlask,
            self::FrameworkPlainPython,
            self::FrameworkRails,
            self::FrameworkSinatra,
            self::FrameworkPlainRuby,
            self::FrameworkOther => ProjectStackCategory::FRAMEWORK,

            self::PlatformLaravelCloud,
            self::PlatformLaravelForge,
            self::PlatformLaravelVapor,
            self::PlatformDockerCompose,
            self::PlatformKubernetes,
            self::PlatformAWS,
            self::PlatformDigitalOcean,
            self::PlatformVercel,
            self::PlatformNetlify,
            self::PlatformRender,
            self::PlatformFlyIO,
            self::PlatformSelfHosted => ProjectStackCategory::PLATFORM,
        };
    }

    /**
     * @return array<ProjectStackTag>
     */
    public static function languageTags(): array
    {
        return [
            self::LanguagePHP,
            self::LanguageJavaScript,
            self::LanguagePython,
            self::LanguageRuby,
            self::LanguageOther,
        ];
    }

    /**
     * @return array<ProjectStackTag>
     */
    public static function frameworkTags(): array
    {
        return [
            self::FrameworkLaravel,
            self::FrameworkSymfony,
            self::FrameworkYii2,
            self::FrameworkCakePHP,
            self::FrameworkCodeIgniter,
            self::FrameworkPlainPHP,
            self::FrameworkNode,
            self::FrameworkNext,
            self::FrameworkRemix,
            self::FrameworkNuxt,
            self::FrameworkSvelteKit,
            self::FrameworkAstro,
            self::FrameworkSolidStart,
            self::FrameworkVanillaJS,
            self::FrameworkDjango,
            self::FrameworkFastAPI,
            self::FrameworkFlask,
            self::FrameworkPlainPython,
            self::FrameworkRails,
            self::FrameworkSinatra,
            self::FrameworkPlainRuby,
            self::FrameworkOther,
        ];
    }

    /**
     * @return array<ProjectStackTag>
     */
    public static function platformTags(): array
    {
        return [
            self::PlatformLaravelCloud,
            self::PlatformLaravelForge,
            self::PlatformLaravelVapor,
            self::PlatformDockerCompose,
            self::PlatformKubernetes,
            self::PlatformAWS,
            self::PlatformDigitalOcean,
            self::PlatformVercel,
            self::PlatformNetlify,
            self::PlatformRender,
            self::PlatformFlyIO,
            self::PlatformSelfHosted,
        ];
    }

    /**
     * @return array<string>
     */
    public static function languageValues(): array
    {
        return array_map(static fn (self $tag) => $tag->value, self::languageTags());
    }

    /**
     * @return array<string>
     */
    public static function frameworkValues(): array
    {
        return array_map(static fn (self $tag) => $tag->value, self::frameworkTags());
    }

    /**
     * @return array<string>
     */
    public static function platformValues(): array
    {
        return array_map(static fn (self $tag) => $tag->value, self::platformTags());
    }
}
