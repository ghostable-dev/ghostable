<?php

namespace App\Core\Http\Controllers;

use Illuminate\Support\Carbon;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GeneratePagesSitemap extends Controller
{
    public function __invoke()
    {
        return Sitemap::create()
            ->add($this->home())
            ->add($this->pricing())
            ->add($this->contact())
            ->add($this->terms())
            ->add($this->privacy())
            ->add($this->integrationsIndex())
            ->add($this->vantaIntegration())
            ->add($this->forgeIntegration())
            ->add($this->cloudIntegration())
            ->add($this->vaporIntegration())
            ->toResponse(request());
    }

    private function home(): Url
    {
        return Url::create(url('/'))
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_ALWAYS)
            ->setPriority(1.0);
    }

    private function pricing(): Url
    {
        return Url::create(route('pricing'))
            ->setLastModificationDate(
                Carbon::create(year: 2025, month: 9, day: 1)
            )->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.9);
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
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }

    private function integrationsIndex(): Url
    {
        return Url::create(route('integrations.index'))
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.75);
    }

    private function forgeIntegration(): Url
    {
        return Url::create(route('integrations.forge'))
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }

    private function cloudIntegration(): Url
    {
        return Url::create(route('integrations.cloud'))
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }

    private function vaporIntegration(): Url
    {
        return Url::create(route('integrations.vapor'))
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
            ->setPriority(0.7);
    }
}
