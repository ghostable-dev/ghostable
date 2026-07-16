<?php

namespace App\Core\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GeneratePagesSitemap extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = Sitemap::create()
            ->add($this->home())
            ->add($this->download())
            ->add($this->pricing())
            ->add($this->licenses())
            ->add($this->trust())
            ->add($this->contact())
            ->add($this->securityReport())
            ->add($this->terms())
            ->add($this->privacy())
            ->add($this->integrationsIndex())
            ->add($this->vantaIntegration())
            ->add($this->forgeIntegration())
            ->add($this->cloudIntegration())
            ->add($this->openclawIntegration())
            ->add($this->vaporIntegration());

        foreach ($this->documentationRoutes() as $routeName) {
            $sitemap->add($this->documentation($routeName));
        }

        return $sitemap->toResponse(request());
    }

    private function home(): Url
    {
        return Url::create(url('/'))
            ->setLastModificationDate($this->modifiedOn(2026, 3, 26))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_ALWAYS)
            ->setPriority(1.0);
    }

    private function pricing(): Url
    {
        return Url::create(route('pricing'))
            ->setLastModificationDate($this->modifiedOn(2026, 4, 9))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.9);
    }

    private function download(): Url
    {
        return Url::create(route('download'))
            ->setLastModificationDate($this->modifiedOn(2026, 7, 15))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.9);
    }

    private function licenses(): Url
    {
        return Url::create(route('licenses'))
            ->setLastModificationDate($this->modifiedOn(2026, 7, 14))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.9);
    }

    private function trust(): Url
    {
        return Url::create(route('trust'))
            ->setLastModificationDate($this->modifiedOn(2026, 3, 26))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.7);
    }

    // @codeCoverageIgnoreStart
    private function contact(): Url
    {
        return Url::create(route('contact'))
            ->setLastModificationDate(
                Carbon::create(year: 2025, month: 9, day: 1)
            )->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.9);
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    private function securityReport(): Url
    {
        return Url::create(route('security.report'))
            ->setLastModificationDate(
                Carbon::create(year: 2025, month: 9, day: 1)
            )->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.9);
    }
    // @codeCoverageIgnoreEnd

    private function terms(): Url
    {
        return Url::create(route('terms'))
            ->setLastModificationDate(
                Carbon::create(year: 2025, month: 6, day: 23)
            )->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.3);
    }

    private function privacy(): Url
    {
        return Url::create(route('privacy'))
            ->setLastModificationDate(
                Carbon::create(year: 2025, month: 6, day: 23)
            )->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.5);
    }

    private function vantaIntegration(): Url
    {
        return Url::create(route('integrations.vanta'))
            ->setLastModificationDate($this->modifiedOn(2026, 3, 17))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }

    private function integrationsIndex(): Url
    {
        return Url::create(route('integrations.index'))
            ->setLastModificationDate($this->modifiedOn(2026, 3, 17))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.75);
    }

    private function forgeIntegration(): Url
    {
        return Url::create(route('integrations.forge'))
            ->setLastModificationDate($this->modifiedOn(2026, 3, 17))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }

    private function cloudIntegration(): Url
    {
        return Url::create(route('integrations.cloud'))
            ->setLastModificationDate($this->modifiedOn(2026, 3, 17))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }

    private function openclawIntegration(): Url
    {
        return Url::create(route('integrations.openclaw'))
            ->setLastModificationDate($this->modifiedOn(2026, 3, 17))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }

    private function vaporIntegration(): Url
    {
        return Url::create(route('integrations.vapor'))
            ->setLastModificationDate($this->modifiedOn(2026, 3, 17))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }

    private function documentation(string $routeName): Url
    {
        return Url::create(route($routeName))
            ->setLastModificationDate($this->modifiedOn(2026, 7, 16))
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(str_starts_with($routeName, 'docs.cli.') ? 0.8 : 0.7);
    }

    /**
     * @return list<string>
     */
    private function documentationRoutes(): array
    {
        return [
            'docs.cli.index',
            'docs.cli.installation',
            'docs.cli.new-projects',
            'docs.cli.existing-projects',
            'docs.cli.team-onboarding',
            'docs.cli.workflows.projects',
            'docs.cli.workflows.environments',
            'docs.cli.workflows.variable-promotions',
            'docs.cli.workflows.devices',
            'docs.cli.workflows.deploy-tokens',
            'docs.cli.workflows.daily-development',
            'docs.cli.workflows.review',
            'docs.cli.workflows.hygiene',
            'docs.cli.automation.continuous-integration',
            'docs.cli.automation.deployments',
            'docs.cli.reference.validation',
            'docs.cli.reference.commands',
            'docs.cli.reference.configuration',
            'docs.cli.reference.security',
            'docs.cli.reference.backups',
            'docs.cli.reference.agents',
            'docs.cli.reference.troubleshooting',
            'docs.desktop.index',
            'docs.desktop.installation',
            'docs.desktop.projects',
            'docs.desktop.interface',
            'docs.desktop.workflows.environments',
            'docs.desktop.workflows.local-files',
            'docs.desktop.workflows.validation-review',
            'docs.desktop.workflows.activity',
            'docs.desktop.workflows.access',
            'docs.desktop.reference.project-settings',
            'docs.desktop.reference.application-settings',
            'docs.desktop.reference.licensing',
            'docs.desktop.reference.security',
            'docs.desktop.reference.troubleshooting',
        ];
    }

    private function modifiedOn(int $year, int $month, int $day): Carbon
    {
        return Carbon::create(year: $year, month: $month, day: $day);
    }
}
