<?php

namespace App\Core\View\Components;

use Spatie\SchemaOrg\Graph;
use Spatie\SchemaOrg\Schema;

class SiteSchema extends SchemaGenerator
{
    public function __construct(
        public ?string $name = null,
        public ?string $url = null,
    ) {
        $siteName = $name ?? config('app.name', 'Ghostable');
        $siteUrl = $this->absoluteUrl($url ?? url('/'));
        $organization = $this->defaultOrganization();

        $this->type = (new Graph)
            ->add($organization)
            ->add(
                Schema::webSite()
                    ->name($siteName)
                    ->url($siteUrl)
                    ->publisher($organization)
                    ->inLanguage('en-US')
            );
    }
}
