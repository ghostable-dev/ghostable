<?php

namespace App\View\Components\Schema;

use Illuminate\View\Component;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Organization;
use Spatie\SchemaOrg\Schema;

abstract class SchemaGenerator extends Component
{
    protected BaseType $type;

    protected function defaultOrganization(): Organization
    {
        return Schema::organization()
            ->name('Ghostable')
            ->legalName('Ghostable, LLC')
            ->telephone(config('contact.phone'))
            ->address(
                Schema::postalAddress()
                    ->streetAddress(config('contact.address.line1'))
                    ->addressLocality(config('contact.address.addressLocality'))
                    ->addressRegion(config('contact.address.addressRegion'))
                    ->postalCode(config('contact.address.postalCode'))
                    ->addressCountry(config('contact.address.addressCountry'))
            )->founders([
                Schema::person()
                    ->name('Joe Rucci')
                    ->email('joe@ghostable.dev')
            ])
            ->sameAs([
                config('contact.social.github'),
                config('contact.social.discord'),
            ])
            ->foundingDate(2025)
            ->url(url('/'));
    }

    public function render(): string
    {
        return $this->type->toScript();
    }
}
