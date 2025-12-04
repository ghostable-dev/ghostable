<?php

namespace App\Core\Http\Controllers;

use App\Learn\LearnRepository;
use Illuminate\Support\Carbon;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateLearnSitemap extends Controller
{
    public function __construct(private LearnRepository $learn) {}

    public function __invoke()
    {
        $sitemap = Sitemap::create()
            ->add($this->learn());

        foreach ($this->learn->all() as $guide) {
            if (! $guide['href']) {
                continue;
            }

            $sitemap->add(
                Url::create($guide['href'])
                    ->setLastModificationDate(Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8)
            );
        }

        return $sitemap->toResponse(request());
    }

    private function learn(): Url
    {
        return Url::create(route('learn.index'))
            ->setLastModificationDate(Carbon::now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.85);
    }
}
